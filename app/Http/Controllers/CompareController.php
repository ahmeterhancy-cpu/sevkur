<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Services\FavoritesService;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function __construct(private readonly FavoritesService $favorites) {}

    public function index()
    {
        $products = $this->favorites->compareProducts();

        return view('pages.compare', [
            'products' => $products,
            'attributes' => Attribute::with('values')->orderBy('position')->get(),
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $result = $this->favorites->toggleCompare(Product::findOrFail($data['product_id']));

        $message = $result['limited']
            ? __('shop.compare_limit', ['limit' => FavoritesService::COMPARE_LIMIT])
            : ($result['added'] ? __('shop.compare_added') : __('shop.compare_removed'));

        if ($request->expectsJson()) {
            return response()->json($result + ['message' => $message]);
        }

        return back()->with($result['limited'] ? 'error' : 'status', $message);
    }
}
