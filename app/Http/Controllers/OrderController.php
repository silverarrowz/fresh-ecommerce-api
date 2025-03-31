<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $items = [];
        $total = 0;


        foreach ($request->input('items') as $item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $product->price;
            $quantity = $item['quantity'];
            $subtotal = $price * $quantity;

            $image = $product->images->first()->url ?? null;

            $items[] = [
                'product_id' => $product->id,
                'title' => $product->title,
                'image' => $image,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }


        $order = Order::create([
            'user_id' => $user ? $user->id : null,
            'items' => $items,
            'total' => $total,
            'payment_status' => 'pending',
        ]);

        return response()->json(['order' => $order], 201);
    }
}
