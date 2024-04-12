<?php
namespace App\Repository\User;

interface UserInterface{

    public function getAllUsers();

    public function findById($id);

    public function create($collection = [] );

    public function updateUser( $id,$collection);


    public function deleteUser($id);
}