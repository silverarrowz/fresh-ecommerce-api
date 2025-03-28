<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $items = [];
        $total = 0;

        // Calculate total and fetch product details
        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $product->price;
            $quantity = $item['quantity'];
            $subtotal = $price * $quantity;

            $items[] = [
                'product_id' => $product->id,
                'title' => $product->title,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        // Save order
        $order = Order::create([
            'user_id' => $user ? $user->id : null,
            'items' => $items,
            'total' => $total,
            'payment_status' => 'pending', // We'll update after Stripe checkout
        ]);

        return response()->json(['order' => $order], 201);
    }
}
