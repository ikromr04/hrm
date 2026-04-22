<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ExperienceStoreRequest;
use App\Http\Requests\Api\V1\ExperienceUpdateRequest;
use App\Http\Resources\Api\V1\ExperienceResource;
use App\Models\Experience;
use App\Queries\Api\V1\ExperienceQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExperienceController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(ExperienceQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExperienceStoreRequest $request): ExperienceResource
    {
        $experience = Experience::create($request->mappedAttributes());

        return $experience->toResource();
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
    public function update(ExperienceUpdateRequest $request, Experience $experience)
    {
        $experience->update($request->mappedAttributes());

        return $experience->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Experience $experience): Response
    {
        $experience->delete();

        return $this->noContent();
    }
}
