<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Stripe tells us about payments here, so the coins still land when the
 * reader never makes it back to the site: they closed the tab, the bank took
 * its time over a BLIK confirmation, the phone lost signal on the way.
 *
 * One Stripe account serves several of Karol's sites, and an endpoint is sent
 * every event of the types it subscribes to — including the other sites'.
 * A payment without this shop's metadata is therefore somebody else's:
 * acknowledged and left alone, never an error, or Stripe would see the
 * failures pile up and switch the endpoint off.
 */
class StripeWebhookController extends Controller
{
    private const HANDLED = ['payment_intent.succeeded', 'payment_intent.payment_failed'];

    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.webhook.secret');

        if (! $secret) {
            report(new \RuntimeException('STRIPE_WEBHOOK_SECRET is missing, so Stripe events cannot be verified.'));

            return response('', 500);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return response('Invalid signature', 400);
        }

        if (! in_array($event->type, self::HANDLED, true)) {
            return response('', 200);
        }

        $intentId = (string) ($event->data->object->id ?? '');

        if ($intentId === '') {
            return response('', 200);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // Only the id is taken from the payload; the payment itself is read
        // back from Stripe. The endpoint's API version then cannot drift away
        // from the one this library speaks, and a BLIK payment that has moved
        // on since the event was queued is seen as it stands now.
        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (\Exception $e) {
            report($e);

            // Stripe will try again later.
            return response('', 500);
        }

        $package = config('coins.packages.'.($intent->metadata['package_id'] ?? ''));
        $user = User::find((int) ($intent->metadata['user_id'] ?? 0));

        if (! $package || ! $user) {
            return response('', 200);
        }

        if ($event->type === 'payment_intent.payment_failed') {
            logger()->info('Stripe payment failed', [
                'payment_intent' => $intent->id,
                'user_id' => $user->id,
                'package_id' => $intent->metadata['package_id'] ?? null,
                'reason' => $intent->last_payment_error->message ?? null,
            ]);

            return response('', 200);
        }

        if ($intent->status !== 'succeeded') {
            return response('', 200);
        }

        if (CoinPurchaseController::credit($user, $intent, $package)) {
            logger()->info('Coins credited by webhook', [
                'payment_intent' => $intent->id,
                'user_id' => $user->id,
                'coins' => $package['coins'],
            ]);
        }

        return response('', 200);
    }
}
