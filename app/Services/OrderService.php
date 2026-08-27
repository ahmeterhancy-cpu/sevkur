<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Support\Locales;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CurrencyService $currency,
    ) {}

    /**
     * Sepeti siparise cevirir. Stok dusumu ayni islem icinde yapilir; yetersiz
     * stok bulunursa islem geri alinir ve StockUnavailable firlatilir.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws StockUnavailableException
     */
    public function placeFromCart(array $data, ShippingMethod $shipping): Order
    {
        $cart = $this->cart->current();

        if ($cart->isEmpty()) {
            throw new StockUnavailableException('cart_empty');
        }

        $totals = $this->cart->totals($shipping);
        $displayCurrency = $this->currency->current();

        return DB::transaction(function () use ($cart, $data, $shipping, $totals, $displayCurrency) {
            // Stoklari kilitleyerek dus.
            foreach ($cart->items as $item) {
                $this->consumeStock($item->product_id, $item->product_variant_id, $item->quantity);
            }

            $order = Order::create([
                'number' => Order::generateNumber(),
                'user_id' => auth()->id(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'paytr',
                'display_currency' => $displayCurrency->code,
                'display_rate' => $displayCurrency->effectiveRate(),
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'shipping_total' => $totals['shipping'],
                'grand_total' => $totals['total'],
                'coupon_code' => $totals['coupon']?->code,
                'shipping_method_id' => $shipping->id,
                'customer_first_name' => $data['first_name'],
                'customer_last_name' => $data['last_name'],
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'],
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? null,
                'note' => $data['note'] ?? null,
                'locale' => app()->getLocale(),
                'ip' => request()->ip(),
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                $order->items()->create([
                    'product_id' => $product?->id,
                    'product_variant_id' => $variant?->id,
                    'name' => $product?->t('name', $order->locale) ?? '-',
                    'sku' => $variant?->sku ?: $product?->sku,
                    'image' => $product?->cover,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->lineTotal(),
                    'options' => $variant ? ['variant' => $variant->t('name', $order->locale)] : null,
                ]);
            }

            if ($totals['coupon']) {
                Coupon::whereKey($totals['coupon']->id)->increment('used_count');
            }

            $order->log('created', 'Siparis olusturuldu');

            return $order;
        });
    }

    /**
     * Stok dusumu — kosullu UPDATE ile yaris kosulunu engeller.
     *
     * @throws StockUnavailableException
     */
    private function consumeStock(?int $productId, ?int $variantId, int $quantity): void
    {
        if (! $productId) {
            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            throw new StockUnavailableException('missing_product');
        }

        if (! $product->manage_stock) {
            return;
        }

        if ($variantId) {
            $affected = ProductVariant::whereKey($variantId)
                ->where('stock', '>=', $quantity)
                ->update(['stock' => DB::raw('stock - '.(int) $quantity)]);
        } else {
            $affected = Product::whereKey($productId)
                ->where('stock', '>=', $quantity)
                ->update(['stock' => DB::raw('stock - '.(int) $quantity)]);
        }

        if ($affected === 0) {
            throw new StockUnavailableException($product->t('name'));
        }
    }

    /** Iptal/basarisiz odemede stogu geri yukler. */
    public function restoreStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::find($item->product_id);

                if (! $product || ! $product->manage_stock) {
                    continue;
                }

                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)
                        ->update(['stock' => DB::raw('stock + '.(int) $item->quantity)]);
                } else {
                    Product::whereKey($item->product_id)
                        ->update(['stock' => DB::raw('stock + '.(int) $item->quantity)]);
                }
            }
        });
    }

    public function markPaid(Order $order, array $payload = []): void
    {
        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order, $payload) {
            $order->forceFill([
                'payment_status' => 'paid',
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
                'paid_at' => now(),
                'payment_payload' => array_merge($order->payment_payload ?? [], $payload),
            ])->save();

            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('sold_count', $item->quantity);
                }
            }

            $order->log('paid', 'Odeme onaylandi');
        });
    }

    public function markFailed(Order $order, string $reason = ''): void
    {
        if ($order->isPaid()) {
            return;
        }

        $this->restoreStock($order);

        $order->forceFill([
            'payment_status' => 'failed',
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ])->save();

        $order->log('failed', 'Odeme basarisiz'.($reason ? ': '.$reason : ''));
    }

    public function cancel(Order $order, string $reason = ''): void
    {
        if ($order->status === 'cancelled') {
            return;
        }

        $this->restoreStock($order);

        $order->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ])->save();

        $order->log('cancelled', 'Siparis iptal edildi'.($reason ? ': '.$reason : ''));
    }

    /** @return array<int, string> */
    public function statuses(): array
    {
        return config('shop.order.statuses');
    }

    public function defaultLocale(): string
    {
        return Locales::default();
    }
}
