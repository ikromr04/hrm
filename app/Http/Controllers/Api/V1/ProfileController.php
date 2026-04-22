<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ProfileStoreRequest;
use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Models\Profile;
use App\Queries\Api\V1\ProfileQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProfileQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProfileStoreRequest $request): ProfileResource
    {
        $profile = Profile::create($request->mappedAttributes());

        return $profile->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(ProfileQuery $query, string $id): ProfileResource
    {
        $profile =$query->query()->findOrFail($id);

        return $profile->toResource();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProfileUpdateRequest $request, Profile $profile): ProfileResource
    {
        $profile->update($request->mappedAttributes());

        return $profile->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profile $profile)
    {
        //
    }
}
