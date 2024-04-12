<?php

namespace App\Repository\Subscripation;

use App\Repository\Subscripation\SubscriptionInterface;
use App\Models\SubscripationPlan;

class SubscriptionRepository implements SubscriptionInterface
{   
   /**
     * @var SubscripationPlan
     */
    private $subscripationPlan;

    /**
     * SubscriptionRepository constructor.
     *
     * @param SubscripationPlan $subscripationPlan
     */
    public function __construct(SubscripationPlan $subscripationPlan)
    {
        $this->subscripationPlan = $subscripationPlan;
    }


    public function getAllPlans()
    {
        return  $this->subscripationPlan->get();
    }

    public function findById($modelId, $columns = ['*'], $relations = [], $appends = []) {
        return $this->subscripationPlan->select($columns)->with($relations)->findOrFail($modelId)->append($appends);
    }

    public function create($collection = [])
    {   
        try {
          

            $arrSubscriptionPlan = [];
            $arrSubscriptionPlan = $this->buildSubscriptionPlanArr($collection, $arrSubscriptionPlan);
            $newPlan = $this->subscripationPlan->create($arrSubscriptionPlan);
            return $newPlan;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function updatePlan($id, $collection)
    {
        try {
            $arrSubscriptionPlan = [];
            $arrSubscriptionPlan=$this->buildSubscriptionPlanArr($collection, $arrSubscriptionPlan);
            return $this->subscripationPlan->where('id', $id)->update($arrSubscriptionPlan);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function deletePlan($id)
    {
        return $this->subscripationPlan->where('id',$id)->delete();
    }

    public function buildSubscriptionPlanArr($collection, $arrSubscriptionPlan)
    {
        $arrSubscriptionPlan['title_en'] = $collection['title_en'];
        $arrSubscriptionPlan['title_ar'] = $collection['title_ar'];
        $arrSubscriptionPlan['tag_line_ar'] = $collection['tag_line_ar'];
        $arrSubscriptionPlan['tag_line_en'] = $collection['tag_line_en'];
        $arrSubscriptionPlan['rate_per_month'] = $collection['price_per_month'];
        $arrSubscriptionPlan['rate_per_year'] = $collection['price_per_year'];
        $arrSubscriptionPlan['nu_of_adding_products'] = $collection['nu_of_adding_products'];
        $arrSubscriptionPlan['nu_of_adding_manager'] = $collection['nu_of_adding_manager'];
        $arrSubscriptionPlan['nu_of_adding_branches'] = $collection['nu_of_adding_branches'];
        $arrSubscriptionPlan['stock_management'] = $collection['stock_management'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['sms_management'] = $collection['sms_management'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['email_management'] = $collection['email_management'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['discount_management'] = $collection['discount_management'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['location_as_busy'] = $collection['location_as_busy'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['car_pickup'] = $collection['car_pickup'] == 'on' ? '1':'0';
        $arrSubscriptionPlan['description_en'] = $collection['description_en'];
        $arrSubscriptionPlan['description_ar'] = $collection['description_ar'];

        return  $arrSubscriptionPlan;
    }
}