<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['locale' => app()->getLocale(), 'is_active' => true],
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('shop.newsletter_ok')]);
        }

        return back()->with('status', __('shop.newsletter_ok'));
    }
}
