<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PayTrService;
use App\Services\StockUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
        private readonly PayTrService $paytr,
    ) {}

    public function index()
    {
        $cart = $this->cart->current();

        if ($cart->isEmpty()) {
            return redirect(localized_route('cart'))->with('error', __('shop.cart_empty'));
        }

        $methods = ShippingMethod::active()->get();

        if ($methods->isEmpty()) {
            return redirect(localized_route('cart'))->with('error', __('shop.no_shipping_method'));
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'methods' => $methods,
            'totals' => $this->cart->totals($methods->first()),
            'addresses' => auth()->check() ? auth()->user()->addresses : collect(),
            'simulation' => $this->paytr->simulationAllowed(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:60'],
            'district' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:400'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'billing_same' => ['nullable', 'boolean'],
            'billing_city' => ['nullable', 'string', 'max:60'],
            'billing_district' => ['nullable', 'string', 'max:60'],
            'billing_address' => ['nullable', 'string', 'max:400'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
            'terms' => ['accepted'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        if ($this->cart->current()->isEmpty()) {
            return redirect(localized_route('cart'))->with('error', __('shop.cart_empty'));
        }

        $problems = $this->cart->stockProblems();

        if ($problems !== []) {
            return redirect(localized_route('cart'))->with('error', __('shop.stock_changed'));
        }

        $shipping = ShippingMethod::active()->findOrFail($data['shipping_method_id']);

        $shippingAddress = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'district' => $data['district'] ?? null,
            'address' => $data['address'],
            'postcode' => $data['postcode'] ?? null,
        ];

        $billingAddress = ! empty($data['billing_same']) ? null : array_filter([
            'city' => $data['billing_city'] ?? null,
            'district' => $data['billing_district'] ?? null,
            'address' => $data['billing_address'] ?? null,
            'postcode' => $data['billing_postcode'] ?? null,
        ]);

        try {
            $order = $this->orders->placeFromCart([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress ?: null,
                'note' => $data['note'] ?? null,
                'payment_method' => 'paytr',
            ], $shipping);
        } catch (StockUnavailableException $e) {
            return redirect(localized_route('cart'))
                ->with('error', __('shop.stock_unavailable', ['product' => $e->subject]));
        }

        if (auth()->check() && ! empty($data['save_address'])) {
            auth()->user()->addresses()->create($shippingAddress + [
                'title' => $data['city'],
                'is_default' => auth()->user()->addresses()->count() === 0,
            ]);
        }

        $this->cart->clear();
        $this->rememberOrder($order);

        return redirect(localized_route('checkout.pay', ['order' => $order->number]));
    }

    public function pay(Order $order)
    {
        abort_unless($this->canView($order), 403);

        if ($order->isPaid()) {
            return redirect(localized_route('checkout.thanks', ['order' => $order->number]));
        }

        // Kimlik bilgileri yoksa yerel gelistirme icin sahte odeme ekrani.
        if ($this->paytr->simulationAllowed()) {
            return view('pages.payment-simulation', ['order' => $order]);
        }

        $result = $this->paytr->createToken(
            $order,
            localized_route('checkout.thanks', ['order' => $order->number]),
            localized_route('checkout.failed', ['order' => $order->number]),
        );

        if (! $result['ok']) {
            return view('pages.payment-error', [
                'order' => $order,
                'error' => $result['error'],
            ]);
        }

        return view('pages.payment', [
            'order' => $order,
            'iframeUrl' => $this->paytr->iframeUrl($result['token']),
        ]);
    }

    /** Yalnizca PayTR kimlik bilgileri tanimsizken calisir. */
    public function simulate(Request $request, Order $order)
    {
        abort_unless($this->paytr->simulationAllowed(), 404);
        abort_unless($this->canView($order), 403);

        if ($request->input('result') === 'success') {
            $this->orders->markPaid($order, ['simulated' => true]);
            $this->sendConfirmation($order);

            return redirect(localized_route('checkout.thanks', ['order' => $order->number]));
        }

        $this->orders->markFailed($order, 'Simulasyon: basarisiz');

        return redirect(localized_route('checkout.failed', ['order' => $order->number]));
    }

    public function thanks(Order $order)
    {
        abort_unless($this->canView($order), 403);

        return view('pages.thanks', ['order' => $order->load('items', 'shippingMethod')]);
    }

    public function failed(Order $order)
    {
        abort_unless($this->canView($order), 403);

        return view('pages.payment-failed', ['order' => $order]);
    }

    public function track(Request $request)
    {
        $order = null;
        $notFound = false;

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'number' => ['required', 'string', 'max:40'],
                'email' => ['required', 'email'],
            ]);

            $order = Order::where('number', $data['number'])
                ->whereRaw('LOWER(customer_email) = ?', [mb_strtolower($data['email'])])
                ->with('items')
                ->first();

            $notFound = $order === null;
        }

        return view('pages.track', compact('order', 'notFound'));
    }

    /**
     * Siparisi goruntuleme hakki: sahibi, ayni oturumda olusturan misafir
     * ya da personel. Misafir siparisleri yalnizca olusturan oturumdan
     * gorulebilir (siparis numarasini bilmek tek basina yetmez).
     */
    private function canView(Order $order): bool
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($order->user_id === $user->id) {
                return true;
            }

            if (in_array($user->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_STAFF], true)) {
                return true;
            }
        }

        return in_array($order->number, session('recent_orders', []), true);
    }

    /** Misafirin kendi siparisini gorebilmesi icin oturuma isaretle. */
    private function rememberOrder(Order $order): void
    {
        $seen = session('recent_orders', []);
        $seen[] = $order->number;
        session(['recent_orders' => array_slice(array_unique($seen), -10)]);
    }

    private function sendConfirmation(Order $order): void
    {
        try {
            Mail::to($order->customer_email)->send(new \App\Mail\OrderPlaced($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
