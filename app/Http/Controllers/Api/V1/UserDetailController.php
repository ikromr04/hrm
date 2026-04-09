<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UserDetailStoreRequest;
use App\Http\Resources\Api\V1\UserDetailCollection;
use App\Http\Resources\Api\V1\UserDetailResource;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class UserDetailController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): UserDetailCollection
    {
        return new UserDetailCollection(UserDetail::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserDetailStoreRequest $request): UserDetailResource
    {
        $detail = UserDetail::create($request->mappedAttributes());

        return new UserDetailResource($detail);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDetail $userDetail): UserDetailResource
    {
        return new UserDetailResource($userDetail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserDetail $userDetail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserDetail $userDetail)
    {
        //
    }
}
