<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\EducationStoreRequest;
use App\Http\Requests\Api\V1\EducationUpdateRequest;
use App\Http\Resources\Api\V1\EducationResource;
use App\Models\Education;
use App\Queries\Api\V1\EducationQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EducationController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(EducationQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
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
     * Update the specified resource in storage.
     */
    public function update(EducationUpdateRequest $request, Education $education): EducationResource
    {
        $education->update($request->mappedAttributes());

        return $education->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Education $education): Response
    {
        $education->delete();

        return $this->noContent();
    }
}
