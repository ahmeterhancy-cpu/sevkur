<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index()
    {
        $cart = $this->cart->current();

        return view('pages.cart', [
            'cart' => $cart,
            'totals' => $this->cart->totals(),
            'shippingMethods' => ShippingMethod::active()->get(),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;

        if ($variant && $variant->product_id !== $product->id) {
            $variant = null;
        }

        $result = $this->cart->add($product, (int) ($data['quantity'] ?? 1), $variant);

        $message = match ($result['reason']) {
            'out_of_stock' => __('shop.out_of_stock'),
            'unavailable' => __('shop.product_unavailable'),
            'stock_capped' => __('shop.stock_capped', ['count' => $result['quantity']]),
            default => __('shop.added_to_cart'),
        };

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $result['ok'],
                'message' => $message,
                'count' => $this->cart->count(),
                'html' => view('partials.mini-cart', [
                    'cart' => $this->cart->current(),
                    'totals' => $this->cart->totals(),
                ])->render(),
            ], $result['ok'] ? 200 : 422);
        }

        return back()->with($result['ok'] ? 'status' : 'error', $message);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $item = $this->findItem((int) $data['item_id']);
        $this->cart->updateQuantity($item, (int) $data['quantity']);

        if ($request->expectsJson()) {
            return $this->jsonState();
        }

        return back()->with('status', __('shop.cart_updated'));
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['item_id' => ['required', 'integer']]);

        $this->cart->remove($this->findItem((int) $data['item_id']));

        if ($request->expectsJson()) {
            return $this->jsonState();
        }

        return back()->with('status', __('shop.cart_item_removed'));
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        $result = $this->cart->applyCoupon($data['code']);

        if ($result['ok']) {
            return back()->with('status', __('shop.coupon_applied'));
        }

        return back()->with('error', __('shop.coupon_'.$result['reason']));
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();

        return back()->with('status', __('shop.coupon_removed'));
    }

    /** Ust menudeki mini sepetin icerigi (AJAX). */
    public function summary()
    {
        return response()->json([
            'count' => $this->cart->count(),
            'html' => view('partials.mini-cart', [
                'cart' => $this->cart->current(),
                'totals' => $this->cart->totals(),
            ])->render(),
        ]);
    }

    private function findItem(int $id): CartItem
    {
        $item = $this->cart->current()->items()->whereKey($id)->first();

        abort_if($item === null, 404);

        return $item;
    }

    private function jsonState()
    {
        $cart = $this->cart->current();
        $totals = $this->cart->totals();

        return response()->json([
            'ok' => true,
            'count' => $cart->itemCount(),
            'subtotal' => money($totals['subtotal']),
            'discount' => money($totals['discount']),
            'total' => money($totals['total']),
            'empty' => $cart->isEmpty(),
            'html' => view('partials.mini-cart', compact('cart', 'totals'))->render(),
        ]);
    }
}
