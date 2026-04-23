<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\EquipmentStoreRequest;
use App\Http\Requests\Api\V1\EquipmentUpdateRequest;
use App\Http\Resources\Api\V1\EquipmentResource;
use App\Models\Equipment;
use App\Queries\Api\V1\EquipmentQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EquipmentController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(EquipmentQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EquipmentStoreRequest $request): EquipmentResource
    {
        $equipment = Equipment::create($request->mappedAttributes());

        return $equipment->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EquipmentUpdateRequest $request, Equipment $equipment): EquipmentResource
    {
        $equipment->update($request->mappedAttributes());

        return $equipment->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipment $equipment): Response
    {
        $equipment->delete();

        return $this->noContent();
    }
}
