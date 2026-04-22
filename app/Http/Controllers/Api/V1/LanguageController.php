<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\LanguageCollection;
use App\Models\Language;
use App\Queries\Api\V1\LanguageQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController
{
    /**
     * Display a listing of the resource.
     */
    public function index(LanguageQuery $query): AnonymousResourceCollection
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Language $language)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        //
    }
}
