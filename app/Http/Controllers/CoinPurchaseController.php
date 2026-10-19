<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CoinPurchase;
use App\Models\Page;
use App\Models\StripeCustomer;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BookReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
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

        return Inertia::render('Coins/Purchase', [
            'packages' => self::packages(),
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
                'automatic_payment_methods' => ['enabled' => true],
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
     * Stripe sends the reader here after paying (and the page calls it
     * directly when no redirect was needed). Coins are granted once per
     * payment, from the package recorded on the intent itself.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $intentId = (string) $request->query('payment_intent', '');
        $returnTo = $request->session()->pull('coins.return_to') ?: route('dashboard', absolute: false);

        if ($intentId === '') {
            return redirect()->route('coins.purchase')->with('error', 'Brak informacji o płatności.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('coins.purchase')->with('error', 'Nie udało się zweryfikować płatności.');
        }

        $user = $request->user();
        $package = config('coins.packages.'.($intent->metadata['package_id'] ?? ''));

        if ($intent->status !== 'succeeded' || ! $package || (int) ($intent->metadata['user_id'] ?? 0) !== $user->id) {
            return redirect()->route('coins.purchase')->with('error', 'Płatność nie została zakończona.');
        }

        $credited = DB::transaction(function () use ($user, $intent, $package) {
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

        return redirect($returnTo)->with(
            'success',
            $credited ? "Hurra! Dodaliśmy {$package['coins']} monet do Twojej sakiewki." : 'Te monety są już w Twojej sakiewce.'
        );
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
