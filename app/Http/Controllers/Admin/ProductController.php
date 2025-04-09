<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ImageService;
use App\Services\PriceChangeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{

    public function __construct(private ImageService $imageService, private PriceChangeService $priceService)
    {
        //
    }

    public function index(): View
    {
        $products = Product::paginate(20);
        return view('admin.products', compact('products'));
    }

    public function edit(Product $product): View
    {
        return view('admin.edit_product', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        // Store the old price before updating
        $oldPrice = $product->price;

        $product->update($request->validated());

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

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully');
    }

    public function create()
    {
        return view('admin.add_product');
    }

    public function store(StoreProductRequest $request): RedirectResponse
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
