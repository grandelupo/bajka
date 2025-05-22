<?php

namespace App\Http\Controllers;

use App\Models\CoinPurchase;
use App\Models\StripeCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CoinPurchaseController extends Controller
{
    public function index(): Response
    {
        $packages = [
            [
                'id' => 'basic',
                'name' => 'Basic',
                'coins' => 100,
                'price' => 5.00,
            ],
            [
                'id' => 'standard',
                'name' => 'Standard',
                'coins' => 500,
                'price' => 20.00,
            ],
            [
                'id' => 'premium',
                'name' => 'Premium',
                'coins' => 1000,
                'price' => 35.00,
            ],
            [
                'id' => 'ultimate',
                'name' => 'Ultimate',
                'coins' => 2500,
                'price' => 75.00,
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
                'currency' => 'usd',
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
} 