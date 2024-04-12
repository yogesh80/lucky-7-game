<?php

namespace App\Repository\Category;

interface CategoryInterface
{
    public function getAll();

    public function store($request);

    public function find($id);

    public function update($request, $id);


}
