<?php

namespace App\Repository\User;

use App\Repository\User\UserInterface;
use App\Models\User;
use Hash;

class UserRepository implements UserInterface
{
   /**
     * @var User
     */
    protected $userRepo;

    /**
     * UserRepository constructor.
     *
     * @param User $userRepo
     */
    public function __construct(User $userRepo)
    {
        $this->userRepo = $userRepo;
    }


    public function getAllUsers()
    {
        return  $this->userRepo->whereHas('roles', function ($query) {
            $query->where('title', '=', 'User');
        })->get();
    }

    public function findById($modelId, $columns = ['*'], $relations = [], $appends = []) {
        return $this->userRepo->select($columns)->with($relations)->findOrFail($modelId)->append($appends);
    }

    public function create($collection = [])
    {
       $user = $this->userRepo->create($collection);
       $user->roles()->attach(4);

       return $user;
    }

    public function updateUser($id,$collection)
    {
        $model = $this->userRepo->where('id',$id)->update([
            'name'=>$collection['name'],
            'email'=>$collection['email'],
            'password'=>Hash::make($collection['password']),
            'phone'=>$collection['phone'],
        ]);
        return $model;
    }

    public function deleteUser($id)
    {
        return $this->userRepo->where('id',$id)->delete();
    }
}
