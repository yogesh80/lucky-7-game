<?php

namespace App\Repository\Category;
use App\Models\{Role, User,Category};
use Auth;
use Gate;
use DB;
use Cache;

use App\Traits\ImageUpload;
use Cviebrock\EloquentSluggable\Services\SlugService;

class CategoryRepository implements CategoryInterface
{
    /**
     * @var Category
     */
    use ImageUpload;

    private $categoryRepo;

    /**
     * CategoryRepository constructor.
     *
     * @param Category $categoryRepo
     */
    public function __construct(Category $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    public function getAll()
    {
            $categories = $this->categoryRepo->orderBy('sort','ASC')->get();
            return array('status' => true, 'data' => $categories);
    }

    public function store($data)
    {
            $objCategory = $this->categoryRepo;
            DB::begintransaction();
            if (isset($data['category_image']) && is_file($data['category_image'])) {
                $objCategory->image = $this->fileUpload($data['category_image'],'Store/categories');
            }
            $this->buildObject($data, $objCategory,'create');
            $objCategory->description=SlugService::createSlug(Category::class, 'description', $data['title']);
            $response = $objCategory->save();
            if ($response) {
                DB::commit();
                return array('status' => true, 'message' => trans('menu_option.category_create_success'));
            }
            return array('status' => false, 'message' => trans('menu_option.category_create_failed'));

    }



    public function find($id)
    {
            $category = $this->categoryRepo->findorfail($id);
            if($category){
                return array('status' => true, 'data' => $category);
               }
            return array('status' => false, 'message' => 'category not found');
    }

    public function update($data, $id)
    {
            $objCategory = $this->categoryRepo->findorfail($id);
            DB::begintransaction();
            if (isset($data['category_image']) && is_file($data['category_image'])) {
                $objCategory->image = $this->fileUpload($data['category_image'],'Store/categories');

            }
            $this->buildObject($data, $objCategory,'update');
            $response = $objCategory->save();
            if ($response) {
                DB::commit();

                return array('status' => true, 'message' => trans('menu_option.category_update_success'));
            }
            return array('status' => false, 'message' => 'Something went wrong');
    }

    public function buildObject($data, $objCategory,$status)
    {
        $objCategory->name = $data['title'];
        $objCategory->is_active =$data['is_active'] ?? '1';
        Cache::forget('categories');
        return $objCategory;

    }






}
