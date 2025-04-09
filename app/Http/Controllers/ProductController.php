<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ExchangeRateService;

class ProductController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
        //
    }

    public function index()
    {
        $products = Product::all();
        $exchangeRate = $this->exchangeRateService->getRate();

        return view('products.list', compact('products', 'exchangeRate'));
    }

    public function show(Request $request, Product $product)
    {
        $exchangeRate = $this->exchangeRateService->getRate();

        return view('products.show', compact('product', 'exchangeRate'));
    }
}
