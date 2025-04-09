<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ExchangeRateService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
        //
    }

    public function index(): View
    {
        $products = Product::latest()->paginate(20);
        $exchangeRate = $this->exchangeRateService->getRate();

        return view('products.list', compact('products', 'exchangeRate'));
    }

    public function show(Request $request, Product $product): View
    {
        $exchangeRate = $this->exchangeRateService->getRate();

        return view('products.show', compact('product', 'exchangeRate'));
    }
}
