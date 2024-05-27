<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    protected $cart;
    protected $cartItem;

    public function __construct(Cart $cart, CartItem $cartItem)
    {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
    }

    public function index(Request $request)
    {
        $userId = $request->header('User-ID');
        $cart = $this->cart->where('uuid', $userId)->with('items')->first();
        
        return response()->json($cart);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1'
        ]);

        $userId = $request->header('User-ID');
        $cart = $this->cart->firstOrCreate(['uuid' => $userId], ['total_amount' => 0]);

        $cartItem = $this->cartItem->create([
            'cart_id' => $cart->id,
            'product_id' => $request->product_id,
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity
        ]);

        $cart->total_amount += $cartItem->price * $cartItem->quantity;
        $cart->save();

        return response()->json($cart);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = $this->cartItem->find($id);
        if ($cartItem) {
            $cart = $cartItem->cart;
            $cart->total_amount -= $cartItem->price * $cartItem->quantity;

            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            $cart->total_amount += $cartItem->price * $cartItem->quantity;
            $cart->save();

            return response()->json(['message' => 'Cart updated successfully']);
        } else {
            return response()->json(['message' => 'Item not found'], 404);
        }
    }

    public function destroy($id)
    {
        $cartItem = $this->cartItem->find($id);
        if ($cartItem) {
            $cart = $cartItem->cart;
            $cart->total_amount -= $cartItem->price * $cartItem->quantity;
            $cart->save();
            $cartItem->delete();

            return response()->json(['message' => 'Item removed from cart']);
        } else {
            return response()->json(['message' => 'Item not found'], 404);
        }
    }

    public function clear(Request $request)
    {
        $userId = $request->header('User-ID');
        $cart = $this->cart->where('uuid', $userId)->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->total_amount = 0;
            $cart->save();

            return response()->json(['message' => 'Cart cleared']);
        } else {
            return response()->json(['message' => 'Cart not found'], 404);
        }
    }
}

