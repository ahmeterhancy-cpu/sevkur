<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use Tests\ShopTestCase;

class AccountTest extends ShopTestCase
{
    public function test_visitor_can_register_and_is_logged_in(): void
    {
        $this->post(localized_route('register.post'), [
            'name' => 'Yeni Müşteri',
            'email' => 'yeni@example.com',
            'password' => 'gizli-sifre-123',
            'password_confirmation' => 'gizli-sifre-123',
            'terms' => 1,
        ])->assertRedirect(localized_route('account.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'yeni@example.com', 'role' => 'customer']);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $this->post(localized_route('login.post'), [
            'email' => 'musteri@example.com',
            'password' => 'yanlis',
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_disabled_account_cannot_log_in(): void
    {
        User::where('email', 'musteri@example.com')->update(['is_active' => false]);

        $this->post(localized_route('login.post'), [
            'email' => 'musteri@example.com',
            'password' => 'password',
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_account_pages_require_authentication(): void
    {
        $this->get(localized_route('account.index'))->assertRedirect();
        $this->get(localized_route('account.orders'))->assertRedirect();
    }

    public function test_customer_can_manage_addresses(): void
    {
        $user = User::where('email', 'musteri@example.com')->firstOrFail();
        $this->actingAs($user);

        $this->post(localized_route('account.addresses.store'), [
            'title' => 'Ev',
            'first_name' => 'Deniz',
            'last_name' => 'Yılmaz',
            'phone' => '+90 555 000 00 00',
            'city' => 'Edirne',
            'address' => 'Sanayi Mah. No: 1',
            'is_default' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'is_default' => true]);

        $address = $user->addresses()->first();

        $this->delete(localized_route('account.addresses.destroy', ['address' => $address->id]))->assertRedirect();
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_cannot_delete_another_users_address(): void
    {
        $owner = User::where('email', 'musteri@example.com')->firstOrFail();
        $address = $owner->addresses()->create([
            'first_name' => 'Deniz', 'last_name' => 'Yılmaz', 'phone' => '1',
            'city' => 'Edirne', 'address' => 'Adres',
        ]);

        $intruder = User::create([
            'name' => 'Davetsiz', 'email' => 'davetsiz@example.com',
            'password' => 'sifre-12345', 'role' => User::ROLE_CUSTOMER, 'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->delete(localized_route('account.addresses.destroy', ['address' => $address->id]))
            ->assertForbidden();

        $this->assertDatabaseCount('addresses', 1);
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $user = User::where('email', 'musteri@example.com')->firstOrFail();
        $this->actingAs($user);

        $this->put(localized_route('account.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'yeni-sifre-123',
            'password_confirmation' => 'yeni-sifre-123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_customer_can_only_see_own_order(): void
    {
        $owner = User::where('email', 'musteri@example.com')->firstOrFail();

        $this->actingAs($owner);
        $this->postJson(localized_route('cart.add'), ['product_id' => $this->product(1)->id]);
        $this->post(localized_route('checkout.store'), [
            'first_name' => 'Deniz', 'last_name' => 'Yılmaz',
            'email' => 'deniz@example.com', 'phone' => '1',
            'city' => 'Edirne', 'address' => 'Adres',
            'shipping_method_id' => ShippingMethod::where('code', 'standart')->first()->id,
            'billing_same' => 1, 'terms' => 1,
        ]);

        $order = Order::firstOrFail();

        $intruder = User::create([
            'name' => 'Davetsiz', 'email' => 'davetsiz2@example.com',
            'password' => 'sifre-12345', 'role' => User::ROLE_CUSTOMER, 'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->get(localized_route('account.order', ['order' => $order->number]))
            ->assertForbidden();
    }

    public function test_wishlist_survives_login(): void
    {
        $product = $this->product(1);

        $this->postJson(localized_route('wishlist.toggle'), ['product_id' => $product->id])
            ->assertOk()->assertJson(['added' => true, 'count' => 1]);

        $user = User::where('email', 'musteri@example.com')->firstOrFail();

        $this->post(localized_route('login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_compare_list_is_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $response = $this->postJson(localized_route('compare.toggle'), [
                'product_id' => $this->product($i)->id,
            ])->assertOk();

            if ($i === 5) {
                $response->assertJson(['limited' => true]);
            }
        }

        $this->assertDatabaseCount('compare_items', 4);
    }

    public function test_admin_can_access_filament_panel_but_customer_cannot(): void
    {
        $admin = User::where('email', 'admin@sevkurmobilya.com')->firstOrFail();
        $customer = User::where('email', 'musteri@example.com')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertSuccessful();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }
}
