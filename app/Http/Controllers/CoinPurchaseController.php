<?php

namespace App\Http\Controllers;

use App\Models\CoinPurchase;
use App\Models\StripeCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Book;
use App\Models\Transaction;

class CoinPurchaseController extends Controller
{
    public function index(): Response
    {
        $packages = [
            [
                'id' => 'basic',
                'name' => 'Podstawowy pakiet',
                'coins' => 100,
                'price' => 20.00,
            ],
            [
                'id' => 'standard',
                'name' => 'Mała historia',
                'coins' => 500,
                'price' => 80.00,
            ],
            [
                'id' => 'premium',
                'name' => 'Wielka opowieść',
                'coins' => 1000,
                'price' => 145.00,
            ],
            [
                'id' => 'ultimate',
                'name' => 'Bajkowy świat',
                'coins' => 2500,
                'price' => 255.00,
            ],
        ];

        return Inertia::render('Coins/Purchase', [
            'packages' => $packages,
            'stripeKey' => config('services.stripe.key'),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
            'payment_intent_id' => 'required|string',
        ]);

        $packages = [
            'basic' => ['coins' => 100, 'price' => 5.00],
            'standard' => ['coins' => 500, 'price' => 20.00],
            'premium' => ['coins' => 1000, 'price' => 35.00],
            'ultimate' => ['coins' => 2500, 'price' => 75.00],
        ];

        $package = $packages[$request->package_id] ?? null;

        if (!$package) {
            return back()->with('error', 'Invalid package selected');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status !== 'succeeded') {
                return back()->with('error', 'Payment not successful');
            }

            $user = auth()->user();

            // Create or update Stripe customer
            $stripeCustomer = StripeCustomer::updateOrCreate(
                ['user_id' => $user->id],
                ['stripe_customer_id' => $paymentIntent->customer]
            );

            // Create coin purchase record
            CoinPurchase::create([
                'user_id' => $user->id,
                'amount' => $package['coins'],
                'price' => $package['price'],
                'status' => 'completed',
                'stripe_payment_id' => $paymentIntent->id,
                'stripe_customer_id' => $paymentIntent->customer,
            ]);

            // Add coins to user's balance
            $user->increment('coins', $package['coins']);

            return redirect()->route('dashboard')
                ->with('success', "Successfully purchased {$package['coins']} coins!");
        } catch (\Exception $e) {
            return back()->with('error', 'Payment processing failed');
        }
    }

    public function history(): Response
    {
        $purchases = auth()->user()->coinPurchases()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Coins/History', [
            'purchases' => $purchases,
        ]);
    }

    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
        ]);

        $packages = [
            'basic' => ['coins' => 100, 'price' => 5.00],
            'standard' => ['coins' => 500, 'price' => 20.00],
            'premium' => ['coins' => 1000, 'price' => 35.00],
            'ultimate' => ['coins' => 2500, 'price' => 75.00],
        ];

        $package = $packages[$request->package_id] ?? null;

        if (!$package) {
            return response()->json(['message' => 'Invalid package selected'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $package['price'] * 100, // Convert to cents
                'currency' => 'pln',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'package_id' => $request->package_id,
                    'coins' => $package['coins'],
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create payment intent'], 500);
        }
    }

    public function purchasePage(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'page_number' => 'required|integer|min:1',
            'purchase_next' => 'boolean',
        ]);

        $book = Book::findOrFail($request->book_id);
        $page = $book->pages()->where('page_number', $request->page_number)->firstOrFail();
        $user = auth()->user();

        // Calculate total cost
        $totalCost = $book->price_per_page;
        $pagesToPurchase = [$page];

        // If we need to purchase the next page as well
        if ($request->purchase_next) {
            $nextPage = $book->pages()->where('page_number', $request->page_number + 1)->first();
            if ($nextPage) {
                $totalCost += $book->price_per_page;
                $pagesToPurchase[] = $nextPage;
            }
        }

        // Check if user has enough coins
        if ($user->coins < $totalCost) {
            return response()->json(['message' => 'Not enough coins'], 400);
        }

        $purchasedPageIds = [];

        // Process each page
        foreach ($pagesToPurchase as $pageToPurchase) {
            // Check if page is already purchased
            $hasPurchased = $user->transactions()
                ->where('book_id', $book->id)
                ->where('page_id', $pageToPurchase->id)
                ->exists();

            if (!$hasPurchased) {
                // Create transaction and deduct coins
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'page_id' => $pageToPurchase->id,
                    'coins_spent' => $book->price_per_page,
                ]);

                $purchasedPageIds[] = $pageToPurchase->id;
            }
        }

        // Deduct total coins
        $user->decrement('coins', $totalCost);

        return response()->json([
            'message' => 'Page(s) purchased successfully',
            'purchased_page_ids' => $purchasedPageIds,
            'remaining_coins' => $user->coins,
        ]);
    }
} 