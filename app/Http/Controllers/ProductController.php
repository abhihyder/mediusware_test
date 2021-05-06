<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use yajra\DataTables\DataTables;

class ProductController extends Controller
{
    private $colors = [];
    private $sizes = [];
    private $styles = [];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = "SELECT p.*, pv.variant, pvp.price, pvp.stock from products p left join product_variants pv on pv.product_id=p.id left join product_variant_prices pvp on pvp.product_id=p.id where p.id >0";
           
            if ($request->title) {
                $query .= " and p.title  LIKE '" . $request->title . "%'";
            }
            if ($request->variant) {
                $query .= " and pv.variant_id =" . $request->variant;
            }
            if ($request->price_from && $request->price_to) {
                $query .= " and pvp.price BETWEEN " . $request->price_from . " AND " . $request->price_to;
            }
            if ($request->date) {
                $query .= " and p.created_at BETWEEN '" . date('Y-m-d 00:00:00', strtotime($request->date)) . "' AND '" . date('Y-m-d 23:59:59', strtotime($request->date)) . "'";
            }
            $query .= " order by p.id desc";
            $product = DB::select($query);
            return Datatables::of($product)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $id = $data->id;
                    return "<a class='btn btn-success btn-sm' href='" . route('product.edit', $id) . "'>Edit</a>";
                })
                ->make(true);
        }
        $variants = Variant::all();
        return view('products.index', compact('variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $product = new Product();
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();

            $this->storeProductVariant($request, $product);
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function storeProductVariant(Request $request, $product)
    {
        $i = 1;
        foreach ($request->product_variant as $pv) {
            foreach ($pv['tags'] as $tag) {
                $variant = new ProductVariant();
                $variant->variant = $tag;
                $variant->variant_id = $pv['option'];
                $variant->product_id = $product->id;
                $variant->save();

                if ($i == 1) {
                    $this->colors[] = $variant->id;
                } else if ($i == 2) {
                    $this->sizes[] = $variant->id;
                } else if ($i == 3) {
                    $this->styles[] = $variant->id;
                }
            }
            $i++;
        }

        $this->storeProductVariantPrice($request, $product->id);
        return true;
    }

    public function storeProductVariantPrice(Request $request, $product_id)
    {
        $i = 0;
        foreach ($this->colors as $color) {
            foreach ($this->sizes as $size) {
                foreach ($this->styles as $style) {
                    $variantPrice = new ProductVariantPrice();
                    $variantPrice->product_variant_one  = $color;
                    $variantPrice->product_variant_two  = $size;
                    $variantPrice->product_variant_three  = $style;
                    $variantPrice->price  = $request->product_variant_prices[$i]['price'];
                    $variantPrice->stock  = $request->product_variant_prices[$i]['stock'];
                    $variantPrice->product_id  = $product_id;
                    $variantPrice->save();
                    $i++;
                }
            }
        }

        $this->colors = [];
        $this->sizes = [];
        $this->styles = [];
        return true;
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('product', 'variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        dd($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
