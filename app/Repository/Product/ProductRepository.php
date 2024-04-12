<?php

namespace App\Repository\Product;

use Cviebrock\EloquentSluggable\Services\SlugService;
use App\Models\{Category, Product,ProductOption};
use Illuminate\Support\Facades\Storage;
use Image;
use App\Traits\ImageUpload;

use Auth;
use DB;
class ProductRepository implements ProductInterface
{
    use ImageUpload;

    public function index(){
        $products = Product::with('productCategories:id,name')->where([['deleted_at',NULL]])->orderBy('position','ASC')->get();
         return array('status' => true, 'data' => $products);
    }

  

    public function store($data)
    {
        try {
               DB::begintransaction();
               $imgData=[];
              if(isset($data['document']) && count($data['document']) > 0) {
                      $imgData = $this->buildImageArr($data['document']);
               }

           $data['cover_image']= json_encode($imgData,true);
           $data['position']=1;
           $data['slug']=SlugService::createSlug(Product::class, 'slug', $data['name']);

           $product = Product::create($data);
           $product->productCategories()->sync($data['categories'], []);

            DB::commit();
            return array('status' => true, 'message' => 'Product created successfully!');

        } catch (Exception $e) {
            DB::rollback();
            return array('status' => false, 'message' => $e->getMessage);
        }
    }



    public function buildImageArr($images)
    {
        $imgData = [];
        foreach ($images as $file) {
            $imgData[] = $file;
        }
        return $imgData;
    }

     public function edit($id)
    {
        try {
            $product = Product::where('id',$id)->with('productCategories')->first();
            $imgData=$product->cover_image;
            $categories = DB::table('categories')->where([['is_active','1'],['deleted_at',NULL]])->get();
            $response = [];
            $response['product'] = $product;
            $response['imgData'] = $imgData;
            $response['categories'] = $categories;
            return array('status' => true, 'data' => $response);
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }

    public function update($data, $id)
    {
        try {
            $product = Product::findOrFail($id);
             $imgData=[];
              if(isset($data['document']) && count($data['document']) > 0) {
                      $imgData = $this->buildImageArr($data['document']);
               }
            $data['cover_image']= json_encode($imgData,true);
            $product->update($data);
         
            $product->productCategories()->detach();
            $product->productCategories()->sync($data['categories'], []);
            return array('status' => true, 'message' => 'Product updated successfully!');
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }


}
