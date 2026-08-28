<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Support\Str;

class CartService
{
    public const COOKIE = 'sevkur_cart';

    private ?Cart $cart = null;

    /** Onbellege alinan sepetin ait oldugu oturum jetonu. */
    private ?string $cachedToken = null;

    /** Oturum/kullanici icin sepeti bulur, yoksa olusturur. */
    public function current(): Cart
    {
        $token = session(self::COOKIE);

        // Oturum degistiyse (cikis, yeni ziyaretci) onbellek gecersizdir.
        if ($this->cart && $this->cachedToken === $token && $token !== null) {
            return $this->cart;
        }

        if (! $token) {
            $token = (string) Str::uuid();
            session([self::COOKIE => $token]);
        }

        $cart = Cart::firstOrCreate(['token' => $token]);

        // Giris yapildiysa misafir sepetini kullaniciya bagla.
        if (auth()->check()) {
            $userCart = Cart::where('user_id', auth()->id())->where('id', '!=', $cart->id)->first();

            if ($userCart) {
                $this->merge($userCart, $cart);
                $cart = $cart->fresh();
            }

            if ($cart->user_id !== auth()->id()) {
                $cart->update(['user_id' => auth()->id()]);
            }
        }

        $this->cachedToken = $token;

        return $this->cart = $cart->load('items.product.optionPrices', 'items.variant');
    }

    /** Eski (kullaniciya ait) sepeti guncel sepete tasir. */
    private function merge(Cart $from, Cart $into): void
    {
        foreach ($from->items as $item) {
            $existing = $into->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $item->quantity);
            } else {
                $into->items()->create($item->only([
                    'product_id', 'product_variant_id', 'quantity', 'unit_price',
                ]));
            }
        }

        $from->delete();
    }

    /**
     * Sepete urun ekler. Stok yetersizse eklenebilen azami adede kirpar.
     *
     * @return array{ok: bool, reason: string|null, quantity: int}
     */
    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): array
    {
        $quantity = max(1, $quantity);
        $cart = $this->current();

        if (! $product->is_active) {
            return ['ok' => false, 'reason' => 'unavailable', 'quantity' => 0];
        }

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        $desired = ($item?->quantity ?? 0) + $quantity;
        $available = $this->availableStock($product, $variant);

        if ($available !== null && $available <= 0) {
            return ['ok' => false, 'reason' => 'out_of_stock', 'quantity' => 0];
        }

        $capped = $available !== null ? min($desired, $available) : $desired;
        $unitPrice = $variant ? $variant->effectivePrice() : (float) $product->price;

        if ($item) {
            $item->update(['quantity' => $capped, 'unit_price' => $unitPrice]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $capped,
                'unit_price' => $unitPrice,
            ]);
        }

        $this->cart = null;

        return [
            'ok' => true,
            'reason' => $capped < $desired ? 'stock_capped' : null,
            'quantity' => $capped,
        ];
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();
            $this->cart = null;

            return;
        }

        $available = $this->availableStock($item->product, $item->variant);
        $item->update(['quantity' => $available !== null ? min($quantity, max($available, 1)) : $quantity]);
        $this->cart = null;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
        $this->cart = null;
    }

    public function clear(): void
    {
        $cart = $this->current();
        $cart->items()->delete();
        $cart->update(['coupon_code' => null]);
        $this->cart = null;
    }

    private function availableStock(Product $product, ?ProductVariant $variant): ?int
    {
        if (! $product->manage_stock) {
            return null;
        }

        return $variant ? (int) $variant->stock : (int) $product->stock;
    }

    // ------------------------------------------------------------------ toplam

    /**
     * Sepet toplamlari (hepsi TRY).
     *
     * @return array{subtotal: float, discount: float, shipping: float, total: float, coupon: Coupon|null, free_shipping: bool}
     */
    public function totals(?ShippingMethod $shipping = null): array
    {
        $cart = $this->current();
        $subtotal = $cart->subtotal();

        $coupon = null;
        $discount = 0.0;
        $freeShipping = false;

        if ($cart->coupon_code) {
            $candidate = Coupon::where('code', $cart->coupon_code)->first();

            if ($candidate && $candidate->invalidReason($subtotal, auth()->id()) === null) {
                $coupon = $candidate;
                $discount = $candidate->discountFor($subtotal);
                $freeShipping = (bool) $candidate->free_shipping;
            } else {
                // Gecersiz kalan kuponu sepetten dusur.
                $cart->update(['coupon_code' => null]);
            }
        }

        $shippingCost = 0.0;

        if ($shipping && ! $freeShipping && $subtotal > 0) {
            $shippingCost = $shipping->costFor($subtotal - $discount);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shippingCost, 2),
            'total' => round(max(0, $subtotal - $discount + $shippingCost), 2),
            'coupon' => $coupon,
            'free_shipping' => $freeShipping,
        ];
    }

    /** @return array{ok: bool, reason: string|null} */
    public function applyCoupon(string $code): array
    {
        $cart = $this->current();
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])->first();

        if (! $coupon) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        $reason = $coupon->invalidReason($cart->subtotal(), auth()->id());

        if ($reason !== null) {
            return ['ok' => false, 'reason' => $reason];
        }

        $cart->update(['coupon_code' => $coupon->code]);
        $this->cart = null;

        return ['ok' => true, 'reason' => null];
    }

    public function removeCoupon(): void
    {
        $this->current()->update(['coupon_code' => null]);
        $this->cart = null;
    }

    public function count(): int
    {
        return $this->current()->itemCount();
    }

    /**
     * Odemeye gecmeden once stoklari dogrular.
     *
     * @return array<int, array{item: CartItem, available: int}>
     */
    public function stockProblems(): array
    {
        $problems = [];

        foreach ($this->current()->items as $item) {
            $available = $this->availableStock($item->product, $item->variant);

            if ($available !== null && $item->quantity > $available) {
                $problems[] = ['item' => $item, 'available' => $available];
            }
        }

        return $problems;
    }
}
