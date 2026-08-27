<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::published()->with('category')->latest('published_at');

        if ($slug = $request->query('kategori')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if ($term = trim((string) $request->query('q'))) {
            $like = '%'.mb_strtolower($term).'%';
            $query->whereRaw('LOWER(title) LIKE ?', [$like]);
        }

        return view('pages.blog', [
            'posts' => $query->paginate(9)->withQueryString(),
            'categories' => PostCategory::where('is_active', true)->orderBy('position')->get(),
            'activeCategory' => $request->query('kategori'),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->is_active, 404);

        $post->incrementQuietly('views');

        return view('pages.blog-detail', [
            'post' => $post->load('category', 'author'),
            'related' => Post::published()
                ->where('post_category_id', $post->post_category_id)
                ->whereKeyNot($post->id)
                ->latest('published_at')->take(3)->get(),
        ]);
    }
}
