<?php

namespace App\Repository\Subscripation;

interface SubscriptionInterface{

    public function getAllPlans();

    public function findById($id);

    public function create($collection=[]);

    public function updatePlan($id, $collection);

    public function deletePlan($id);
}