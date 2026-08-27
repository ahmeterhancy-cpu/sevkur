<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Services\FavoritesService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AccountController extends Controller
{
    public function dashboard(FavoritesService $favorites)
    {
        $user = auth()->user();

        return view('pages.account.dashboard', [
            'orders' => $user->orders()->take(5)->get(),
            'orderCount' => $user->orders()->count(),
            'addressCount' => $user->addresses()->count(),
            'wishlistCount' => $favorites->wishlistCount(),
        ]);
    }

    public function orders()
    {
        return view('pages.account.orders', [
            'orders' => auth()->user()->orders()->with('items')->paginate(10),
        ]);
    }

    public function order(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('pages.account.order', [
            'order' => $order->load('items', 'shippingMethod', 'events'),
        ]);
    }

    public function cancelOrder(Order $order, OrderService $orders)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if (! $order->isCancellable()) {
            return back()->with('error', __('shop.order_not_cancellable'));
        }

        if ($order->isPaid()) {
            return back()->with('error', __('shop.order_paid_contact_us'));
        }

        $orders->cancel($order, 'Musteri talebi');

        return back()->with('status', __('shop.order_cancelled'));
    }

    // ------------------------------------------------------------------ adres

    public function addresses()
    {
        return view('pages.account.addresses', [
            'addresses' => auth()->user()->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);

        $address = auth()->user()->addresses()->create($data);

        $this->syncDefault($address, (bool) ($data['is_default'] ?? false));

        return back()->with('status', __('shop.address_saved'));
    }

    public function updateAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $this->validateAddress($request);
        $address->update($data);

        $this->syncDefault($address, (bool) ($data['is_default'] ?? false));

        return back()->with('status', __('shop.address_saved'));
    }

    public function destroyAddress(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return back()->with('status', __('shop.address_deleted'));
    }

    /** @return array<string, mixed> */
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:40'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:60'],
            'district' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:400'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function syncDefault(Address $address, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        auth()->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    // ---------------------------------------------------------------- profil

    public function profile()
    {
        return view('pages.account.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'accepts_marketing' => (bool) ($data['accepts_marketing'] ?? false),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('status', __('shop.profile_updated'));
    }
}
