<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\EquipmentStoreRequest;
use App\Http\Resources\Api\V1\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController
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
    public function store(EquipmentStoreRequest $request): EquipmentResource
    {
        $equipment = Equipment::create($request->mappedAttributes());

        return new EquipmentResource($equipment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equipment $equipment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Equipment $equipment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipment $equipment)
    {
        //
    }
}
