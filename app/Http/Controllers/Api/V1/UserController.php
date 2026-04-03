<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\UserStoreRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

class UserController extends ApiController
{
    public function store(UserStoreRequest $request): UserResource
    {
        $user = User::create($request->mappedAttributes())
            ->refresh();

        return new UserResource($user);
    }
}
