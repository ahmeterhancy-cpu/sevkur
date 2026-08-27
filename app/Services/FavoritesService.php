<?php

namespace App\Services;

use App\Models\CompareItem;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Favoriler ve karsilastirma listesi.
 * Misafirler icin oturumdaki jeton, uyeler icin kullanici kimligi kullanilir.
 */
class FavoritesService
{
    public const SESSION_KEY = 'sevkur_list_token';

    public const COMPARE_LIMIT = 4;

    public function token(): string
    {
        $token = session(self::SESSION_KEY);

        if (! $token) {
            $token = (string) Str::uuid();
            session([self::SESSION_KEY => $token]);
        }

        return $token;
    }

    /** Giris yapinca misafir listesini kullaniciya tasir. */
    public function attachToUser(int $userId): void
    {
        $token = session(self::SESSION_KEY);

        if (! $token) {
            return;
        }

        foreach ([WishlistItem::class, CompareItem::class] as $model) {
            foreach ($model::where('token', $token)->whereNull('user_id')->get() as $row) {
                $exists = $model::where('user_id', $userId)
                    ->where('product_id', $row->product_id)
                    ->exists();

                $exists ? $row->delete() : $row->update(['user_id' => $userId, 'token' => null]);
            }
        }
    }

    // ------------------------------------------------------------- favoriler

    /** @return array{added: bool, count: int} */
    public function toggleWishlist(Product $product): array
    {
        $query = $this->scope(WishlistItem::query());
        $existing = (clone $query)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return ['added' => false, 'count' => $this->wishlistCount()];
        }

        WishlistItem::create($this->owner() + ['product_id' => $product->id]);

        return ['added' => true, 'count' => $this->wishlistCount()];
    }

    /** @return Collection<int, Product> */
    public function wishlistProducts(): Collection
    {
        $ids = $this->scope(WishlistItem::query())->pluck('product_id');

        return Product::whereIn('id', $ids)->active()->get();
    }

    public function wishlistCount(): int
    {
        return $this->scope(WishlistItem::query())->count();
    }

    public function inWishlist(int $productId): bool
    {
        return $this->scope(WishlistItem::query())->where('product_id', $productId)->exists();
    }

    /** @return array<int, int> */
    public function wishlistIds(): array
    {
        return $this->scope(WishlistItem::query())->pluck('product_id')->all();
    }

    // --------------------------------------------------------- karsilastirma

    /** @return array{added: bool, count: int, limited: bool} */
    public function toggleCompare(Product $product): array
    {
        $query = $this->scope(CompareItem::query());
        $existing = (clone $query)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return ['added' => false, 'count' => $this->compareCount(), 'limited' => false];
        }

        if ($this->compareCount() >= self::COMPARE_LIMIT) {
            return ['added' => false, 'count' => $this->compareCount(), 'limited' => true];
        }

        CompareItem::create($this->owner() + ['product_id' => $product->id]);

        return ['added' => true, 'count' => $this->compareCount(), 'limited' => false];
    }

    /** @return Collection<int, Product> */
    public function compareProducts(): Collection
    {
        $ids = $this->scope(CompareItem::query())->pluck('product_id');

        return Product::whereIn('id', $ids)->active()->with(['category', 'brand', 'attributeValues.attribute'])->get();
    }

    public function compareCount(): int
    {
        return $this->scope(CompareItem::query())->count();
    }

    /** @return array<int, int> */
    public function compareIds(): array
    {
        return $this->scope(CompareItem::query())->pluck('product_id')->all();
    }

    // -------------------------------------------------------------- yardimci

    private function scope($query)
    {
        return auth()->check()
            ? $query->where('user_id', auth()->id())
            : $query->whereNull('user_id')->where('token', $this->token());
    }

    /** @return array<string, mixed> */
    private function owner(): array
    {
        return auth()->check()
            ? ['user_id' => auth()->id(), 'token' => null]
            : ['user_id' => null, 'token' => $this->token()];
    }
}
