<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ImageService;
use App\Services\PriceChangeService;

class ProductController extends Controller
{

    public function __construct(private ImageService $imageService, private PriceChangeService $priceService)
    {
        //
    }

    public function index()
    {
        $products = Product::all();
        return view('admin.products', compact('products'));
    }

    public function edit(Product $product)
    {
        return view('admin.edit_product', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // Store the old price before updating
        $oldPrice = $product->price;

        $product->update($request->all());

        if ($request->hasFile('image')) {
            $product->image = 'storage/' . $this->imageService->upload($request->file('image'));
        }

        $product->save();

        // Check if price has changed
        if ($oldPrice != $product->price) {
            $this->priceService->notifyChangeInPrice($product, $oldPrice, $product->price);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully');
    }

    public function create()
    {
        return view('admin.add_product');
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price
        ]);

        if ($request->hasFile('image')) {
            $product->image = 'storage/' . $this->imageService->upload($request->file('image'));
        } else {
            $product->image = 'product-placeholder.jpg';
        }

        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Product added successfully');
    }
}
