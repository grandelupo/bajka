<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CoinPurchase;
use App\Models\Page;
use App\Models\StripeCustomer;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Analytics;
use App\Support\BookReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CoinPurchaseController extends Controller
{
    public static function packages(): array
    {
        return collect(config('coins.packages'))
            ->map(fn (array $package, string $id) => ['id' => $id, ...$package])
            ->values()
            ->all();
    }

    public function index(Request $request): Response
    {
        // Where to send the reader once the coins land, e.g. back to their page.
        $return = $request->query('return');
        if (is_string($return) && str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
            $request->session()->put('coins.return_to', $return);
        }

        // A BLIK payment still waiting for the bank comes back here to be checked again.
        $pending = $request->query('pending');

        return Inertia::render('Coins/Purchase', [
            'packages' => self::packages(),
            'pending' => is_string($pending) && preg_match('/^pi_\w+$/', $pending)
                ? ['id' => $pending, 'tries' => (int) $request->query('tries', 0)]
                : null,
            'stripeKey' => config('services.stripe.key'),
            'returnTo' => $request->session()->get('coins.return_to'),
            'pricePerPage' => (int) (Book::query()->value('price_per_page') ?? 5),
        ]);
    }

    public function history(): Response
    {
        $user = auth()->user();

        return Inertia::render('Coins/History', [
            'purchases' => $user->coinPurchases()->latest()->get(),
            'transactions' => $user->transactions()->with(['book:id,title', 'page:id,page_number'])->latest()->limit(100)->get(),
        ]);
    }

    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate(['package_id' => 'required|string']);

        $package = config("coins.packages.{$request->package_id}");
        if (! $package) {
            return response()->json(['message' => 'Nieznany pakiet'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($package['price'] * 100),
                'currency' => 'pln',
                'payment_method_types' => config('coins.payment_methods'),
                'metadata' => [
                    'package_id' => $request->package_id,
                    'coins' => $package['coins'],
                    'user_id' => $request->user()->id,
                ],
            ]);

            return response()->json(['clientSecret' => $paymentIntent->client_secret]);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['message' => 'Nie udało się rozpocząć płatności'], 500);
        }
    }

    /**
     * Stripe's own checkout page, for readers whose browser blocks
     * js.stripe.com — an ad blocker, a privacy extension, a filtering DNS.
     * Nothing of Stripe's runs on our page here: the reader is simply sent
     * to checkout.stripe.com and comes back to confirm() when they are done.
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $request->validate(['package_id' => 'required|string']);

        $package = config("coins.packages.{$request->package_id}");
        if (! $package) {
            return response()->json(['message' => 'Nieznany pakiet'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::create([
                'mode' => 'payment',
                'locale' => 'pl',
                'customer_email' => $request->user()->email,
                'client_reference_id' => (string) $request->user()->id,
                'payment_method_types' => config('coins.checkout_payment_methods'),
                // Keep the total in złoty. Left on, Stripe converts it to the
                // reader's local currency with a 4% fee, and confirm() would
                // then record that converted amount as the price in PLN.
                'adaptive_pricing' => ['enabled' => false],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'pln',
                        'unit_amount' => (int) round($package['price'] * 100),
                        'product_data' => [
                            'name' => $package['name'],
                            'description' => "{$package['coins']} monet do Bajki",
                        ],
                    ],
                ]],
                // confirm() reads the package off the intent, exactly as it
                // does for a payment made on our own page.
                'payment_intent_data' => [
                    'metadata' => [
                        'package_id' => $request->package_id,
                        'coins' => $package['coins'],
                        'user_id' => $request->user()->id,
                    ],
                ],
                'success_url' => route('coins.purchase.confirm').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('coins.purchase'),
            ]);

            return response()->json(['url' => $session->url]);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['message' => 'Nie udało się otworzyć strony płatności'], 500);
        }
    }

    /**
     * Stripe sends the reader here after paying (and the page calls it
     * directly when no redirect was needed). Coins are granted once per
     * payment, from the package recorded on the intent itself.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $intentId = (string) $request->query('payment_intent', '');
        $sessionId = (string) $request->query('session_id', '');
        $returnTo = $request->session()->get('coins.return_to') ?: route('dashboard', absolute: false);

        if ($intentId === '' && $sessionId === '') {
            return redirect()->route('coins.purchase')->with('error', 'Brak informacji o płatności.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // Coming back from Stripe's own checkout page: the intent hangs off
        // the session, and from there on this is an ordinary payment.
        if ($intentId === '') {
            try {
                $intentId = (string) (Session::retrieve($sessionId)->payment_intent ?? '');
            } catch (\Exception $e) {
                report($e);
            }

            if ($intentId === '') {
                return redirect()->route('coins.purchase')->with('error', 'Nie udało się zweryfikować płatności.');
            }
        }

        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('coins.purchase')->with('error', 'Nie udało się zweryfikować płatności.');
        }

        $user = $request->user();
        $package = config('coins.packages.'.($intent->metadata['package_id'] ?? ''));

        if (! $package || (int) ($intent->metadata['user_id'] ?? 0) !== $user->id) {
            return redirect()->route('coins.purchase')->with('error', 'Płatność nie została zakończona.');
        }

        // BLIK is approved in the bank's app, so the payment can still be on its way.
        if (in_array($intent->status, ['processing', 'requires_action'], true)) {
            return redirect()->route('coins.purchase', ['pending' => $intent->id, 'tries' => (int) $request->query('tries', 0) + 1]);
        }

        if ($intent->status !== 'succeeded') {
            return redirect()->route('coins.purchase')->with('error', 'Płatność nie została zakończona.');
        }

        $request->session()->forget('coins.return_to');

        $credited = self::credit($user, $intent, $package);

        if ($credited) {
            Analytics::report('purchase', [
                'transaction_id' => $intent->id,
                'currency' => 'PLN',
                'value' => $intent->amount / 100,
                'coins' => $package['coins'],
                'package_id' => $intent->metadata['package_id'] ?? null,
            ]);
        }

        return redirect($returnTo)->with(
            'success',
            $credited ? "Hurra! Dodaliśmy {$package['coins']} monet do Twojej sakiewki." : 'Te monety są już w Twojej sakiewce.'
        );
    }

    /**
     * Put the coins in the reader's sakiewka, once per payment. Both the
     * reader coming back from Stripe and the webhook arrive here, and
     * whichever gets there first does the work.
     */
    public static function credit(User $user, PaymentIntent $intent, array $package): bool
    {
        return DB::transaction(function () use ($user, $intent, $package) {
            User::whereKey($user->id)->lockForUpdate()->first();

            if (CoinPurchase::where('stripe_payment_id', $intent->id)->exists()) {
                return false;
            }

            if ($intent->customer) {
                StripeCustomer::updateOrCreate(['user_id' => $user->id], ['stripe_customer_id' => $intent->customer]);
            }

            CoinPurchase::create([
                'user_id' => $user->id,
                'amount' => $package['coins'],
                'price' => $intent->amount / 100,
                'status' => 'completed',
                'stripe_payment_id' => $intent->id,
                'stripe_customer_id' => $intent->customer,
            ]);

            $user->increment('coins', $package['coins']);

            return true;
        });
    }

    /**
     * Unlock pages with coins. Only pages not already owned are charged, and
     * the unlocked pages come back so the reader can show them straight away.
     */
    public function purchasePage(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'page_numbers' => 'required|array|min:1|max:2',
            'page_numbers.*' => 'integer|min:1',
        ]);

        $book = Book::findOrFail($request->book_id);
        $user = $request->user();

        $result = DB::transaction(function () use ($book, $user, $request) {
            $user = User::whereKey($user->id)->lockForUpdate()->first();
            $reader = new BookReader($book, $user);

            $pages = $book->pages()->whereIn('page_number', $request->page_numbers)->get();
            $toBuy = $pages->filter(fn (Page $page) => ! $reader->canRead($page));
            $cost = $toBuy->count() * $book->price_per_page;

            if ($user->coins < $cost) {
                return ['error' => 'Za mało monet', 'needed' => $cost, 'coins' => $user->coins];
            }

            foreach ($toBuy as $page) {
                Transaction::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'page_id' => $page->id,
                    'coins_spent' => $book->price_per_page,
                ]);
            }

            if ($cost > 0) {
                $user->decrement('coins', $cost);
            }

            $reader = new BookReader($book, $user);

            return [
                'pages' => $pages->map(fn (Page $page) => $reader->present($page))->values(),
                'spent' => $cost,
                'remaining_coins' => $user->coins,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error'], ...$result], 402);
        }

        return response()->json($result);
    }
}
