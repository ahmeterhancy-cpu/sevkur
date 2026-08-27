<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FavoritesService;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly FavoritesService $favorites) {}

    public function index()
    {
        return view('pages.wishlist', [
            'products' => $this->favorites->wishlistProducts(),
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $result = $this->favorites->toggleWishlist(Product::findOrFail($data['product_id']));

        if ($request->expectsJson()) {
            return response()->json([
                'added' => $result['added'],
                'count' => $result['count'],
                'message' => $result['added'] ? __('shop.wishlist_added') : __('shop.wishlist_removed'),
            ]);
        }

        return back()->with('status', $result['added'] ? __('shop.wishlist_added') : __('shop.wishlist_removed'));
    }
}
