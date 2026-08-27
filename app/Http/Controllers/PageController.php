<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_active, 404);

        return view('pages.page', ['page' => $page]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function sendContact(Request $request)
    {
        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('error', __('shop.too_many_requests'));
        }

        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'website' => ['nullable', 'size:0'], // bal kupu
        ]);

        unset($data['website']);

        ContactMessage::create($data + ['ip' => $request->ip()]);

        return back()->with('status', __('shop.contact_sent'));
    }

    public function faq()
    {
        return view('pages.faq', [
            'faqs' => Faq::active()->get()->groupBy('group'),
        ]);
    }
}
