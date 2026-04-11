<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ExperienceStoreRequest;
use App\Http\Resources\Api\V1\ExperienceResource;
use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController
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
    public function store(ExperienceStoreRequest $request): ExperienceResource
    {
        $experience = Experience::create($request->mappedAttributes());

        return new ExperienceResource($experience->refresh());
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
