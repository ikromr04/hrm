<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\EducationStoreRequest;
use App\Http\Resources\Api\V1\EducationResource;
use App\Models\Education;
use App\Queries\Api\V1\EducationQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EducationController
{
    /**
     * Display a listing of the resource.
     */
    public function index(EducationQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
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
    public function store(EducationStoreRequest $request): EducationResource
    {
        $education = Education::create($request->mappedAttributes());

        return $education->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
