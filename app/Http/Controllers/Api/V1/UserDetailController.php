<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Requests\V1\UserDetailStoreRequest;
use App\Http\Resources\V1\UserDetailResource;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class UserDetailController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserDetailStoreRequest $request): UserDetailResource
    {
        $detail = UserDetail::create($request->mappedAttributes());

        return new UserDetailResource($detail->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDetail $userDetail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserDetail $userDetail)
    {
        //
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
