<?php
namespace App\Repository\Store;

interface StoreInterface{

    public function getAllStores();

    public function createStore($collection);

    public function getStoreById($id);

}
