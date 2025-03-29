<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
   public function index(Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $cart = $user->cart()->first();
    if (!$cart) {
        return response()->json(['items' => []]);
    }

    $cartItems = $cart->items()->with('product')->get();

    return response()->json($cartItems);
   }

   public function store(Request $request)
   {
       $user = $request->user();

       if (!$user) {
           return response()->json(['message' => 'Unauthorized'], 401);
       }

       $validated = $request->validate([
           'product_id' => 'required|exists:products,id',
           'quantity' => 'required|integer|min:1',
       ]);


       $cart = $user->cart()->first();

       if (!$cart) {
           $cart = Cart::create(['user_id' => $user->id]);
       }

       // Проверяем, есть ли уже такой товар в корзине
       $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();
        // Если такой товар уже есть, обновляем кол-во
       if ($cartItem) {
           $cartItem->quantity += $validated['quantity'];
           $cartItem->save();
       } else {
           // Если товара в корзине нет, создаём его
           CartItem::create([
               'cart_id' => $cart->id,
               'product_id' => $validated['product_id'],
               'quantity' => $validated['quantity'],
           ]);
       }

       // Возвращаем корзину с добавленным товаром
       return $this->index($request);
   }


public function update(Request $request, $productId)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $validated = $request->validate([
        'quantity' => 'required|integer|min:1',
    ]);

    $cart = $user->cart()->first();

    if (!$cart) {
        return response()->json(['message' => 'Cart not found'], 404);
    }

    $cartItem = $cart->items()->where('product_id', $productId)->first();

    if (!$cartItem) {
        return response()->json(['message' => 'Item not found in cart'], 404);
    }

    $cartItem->quantity = $validated['quantity'];
    $cartItem->save();

    return $this->index($request);
}



public function destroy(Request $request, $productId)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $cart = $user->cart()->first();

    if (!$cart) {
        return response()->json(['message' => 'Cart not found'], 404);
    }

    $cartItem = $cart->items()->where('product_id', $productId)->first();

    if (!$cartItem) {
        return response()->json(['message' => 'Item not found in cart'], 404);
    }

    $cartItem->delete();

    return $this->index($request);
}

}
