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

    /**
     * @return float
     */
    private function getExchangeRate()
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://open.er-api.com/v6/latest/USD",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if (!$err) {
                $data = json_decode($response, true);
                if (isset($data['rates']['EUR'])) {
                    return $data['rates']['EUR'];
                }
            }
        } catch (\Exception $e) {

        }

        return env('EXCHANGE_RATE', 0.85);
    }
}
