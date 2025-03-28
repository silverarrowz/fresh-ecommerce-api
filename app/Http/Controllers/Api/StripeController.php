<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;


class StripeController extends Controller
{

    public function createCheckoutSession(Request $request)
    {
        $user = Auth::user();
        $items = $request->input('items');

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['error' => 'No items provided'], 422);
        }

        $lineItems = [];
        $total = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'rub',
                    'product_data' => [
                        'name' => $product->title,
                    ],
                    'unit_amount' => $product->price * 100,
                ],
                'quantity' => $item['quantity'],
            ];

            $total += $product->price * $item['quantity'];
        }

        Stripe::setApiKey(config('stripe.secret'));

        $order = Order::create([
            'user_id' => $user->id,
            'items' => $items,
            'total' => $total,
            'payment_status' => 'pending',

        ]);

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'success_url' => env('STRIPE_SUCCESS_URL'),
            'cancel_url' => env('STRIPE_CANCEL_URL'),
            'metadata' => [
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]
        ]);

        $order->update([
            'stripe_session_id' => $session->id,
        ]);


        return response()->json([
            'url' => $session->url
        ]);
    }
}
