<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function switch(Request $request, CurrencyService $currencies)
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:3']]);

        $currencies->setCurrent(strtoupper($data['code']));

        return back();
    }
}
