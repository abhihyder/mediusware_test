<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use yajra\DataTables\DataTables;

class ProductController extends Controller
{
    private $variants_one = [];
    private $variants_two = [];
    private $variants_three = [];
    private $product_image = [];
    private $product_id = "";
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
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'sku' => 'required',
                'description' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorReponseWithErrors('Validation errors!', $validator->errors());
            }

            DB::beginTransaction();
            $product = new Product();
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();

            $this->storeProductVariant($request, $product);
            DB::commit();
            return $this->successReponseWithData('Product added successfully!', $product);
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function imageUpload(Request $request)
    {
        $this->product_id = $request->product_id;
        $images = $request->file('file');

        if (count($images) > 0) {
            foreach ($images as $image) {
                $this->product_image[] = $image;
            }

            $this->imageStore();
        }
    }


    public function imageStore()
    {
        $i = 1;
        $images =  $this->product_image;
        foreach ($images as $image) {
            $imgName = $this->product_id . "_" . $i . "_" . date("Ymd_His");
            $ext = strtolower($image->getClientOriginalExtension());
            $fullName = $imgName . '.' . $ext;
            $uploadPath = 'public/uploads/products/';
            $uploadTo = $image->move($uploadPath, $fullName);
            $productImage = new ProductImage();
            $productImage->product_id  = $this->product_id;
            $productImage->file_path = $uploadPath . $fullName;
            $productImage->save();
            $i++;
        }
    }

    public function storeProductVariant(Request $request, $product)
    {
        if (count($request->product_variant[0]['tags']) > 0) {

            $i = 1;
            foreach ($request->product_variant as $pv) {
                foreach ($pv['tags'] as $tag) {
                    $variant = new ProductVariant();
                    $variant->variant = $tag;
                    $variant->variant_id = $pv['option'];
                    $variant->product_id = $product->id;
                    $variant->save();

                    if ($i == 1) {
                        $this->variants_one[] = $variant->id;
                    } else if ($i == 2) {
                        $this->variants_two[] = $variant->id;
                    } else if ($i == 3) {
                        $this->variants_three[] = $variant->id;
                    }
                }
                $i++;
            }

            $this->storeProductVariantPrice($request, $product->id);
        }
        return true;
    }

    public function storeProductVariantPrice(Request $request, $product_id)
    {
        $i = 0;

        foreach ($this->variants_one as $v_one) {
            if (count($this->variants_two) > 0) {
                foreach ($this->variants_two as $v_two) {
                    if (count($this->variants_three) > 0) {
                        foreach ($this->variants_three as $v_three) {
                            $this->insertToProductVariantPrice($request, $product_id, $v_one, $v_two, $v_three, $i);
                            $i++;
                        }
                    } else {
                        $this->insertToProductVariantPrice($request, $product_id, $v_one, $v_two, null, $i);
                        $i++;
                    }
                }
            } else {
                $this->insertToProductVariantPrice($request, $product_id, $v_one, null, null, $i);
                $i++;
            }
        }

        $this->variants_one = [];
        $this->variants_two = [];
        $this->variants_three = [];
        return true;
    }

    public function insertToProductVariantPrice(Request $request, $product_id, $v_one, $v_two = null, $v_three = null, $i)
    {
        $variantPrice = new ProductVariantPrice();
        $variantPrice->product_variant_one  = $v_one;
        $variantPrice->product_variant_two  = $v_two;
        $variantPrice->product_variant_three  = $v_three;
        $variantPrice->price  = $request->product_variant_prices[$i]['price'];
        $variantPrice->stock  = $request->product_variant_prices[$i]['stock'];
        $variantPrice->product_id  = $product_id;
        $variantPrice->save();
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
        $productVariants = ProductVariant::where('product_id', $product->id)->get();
        $productVariantPrices = ProductVariantPrice::where('product_id', $product->id)->get();
        $productImages = ProductImage::where('product_id', $product->id)->get();
        $variants = Variant::all();

        $data['product'] = $product;
        $data['productVariants'] = $productVariants;
        $data['productVariantPrices'] = $productVariantPrices;
        $data['productImages'] = $productImages;
        $data['variants'] = $variants;

        return view('products.edit')->with($data);
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
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'sku' => 'required',
                'description' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorReponseWithErrors('Validation errors!', $validator->errors());
            }

            DB::beginTransaction();
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();

            $this->destroyProductVariant($product);
            $this->storeProductVariant($request, $product);

            if (count($request->removed_images) > 0) {
                $this->destroyProductImages($request->removed_images);
            }

            DB::commit();
            return $this->successReponseWithData('Product updated successfully!', $product);
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function destroyProductVariant($product)
    {
        DB::table('product_variants')->where('product_id', $product->id)->delete();
        DB::table('product_variant_prices')->where('product_id', $product->id)->delete();
        return true;
    }

    public function destroyProductImages($images_id)
    {
        $images = ProductImage::whereIn('id', $images_id)->get();
        foreach ($images as $image) {
            if (File::exists($image->file_path)) {
                File::delete($image->file_path);
            }
        }
        ProductImage::whereIn('id', $images_id)->delete();
        return true;
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
