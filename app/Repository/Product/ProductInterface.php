<?php

namespace App\Repository\Product;

interface ProductInterface{

    public function index();


    public function edit($id);

    public function store($data);

    public function update($data, $id);

}
