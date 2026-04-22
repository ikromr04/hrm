<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UserStoreRequest;
use App\Http\Requests\Api\V1\UserUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Queries\Api\V1\UserQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends ApiController
{
    public function index(UserQuery $query): AnonymousResourceCollection
    {
        return $query->get()->toResourceCollection();
    }

    public function store(UserStoreRequest $request): UserResource
    {
        $user = User::create($request->mappedAttributes())
            ->syncRelationships($request->mappedRelationships());


        if ($request->hasFile('data.attributes.avatar')) {
            $user->storeAvatar($request->file('data.attributes.avatar'));
        }

        return $user->toResource();
    }

    public function show(UserQuery $query, string $id): UserResource
    {
        $user = $query->query()->findOrFail($id);

        return $user->toResource();
    }

    public function update(UserUpdateRequest $request, User $user): UserResource
    {
        $user->update($request->mappedAttributes());
        $user->syncRelationships($request->mappedRelationships());

        if ($request->exists('data.attributes.avatar')) {
            if ($request->hasFile('data.attributes.avatar')) {
                $user->deleteAvatar();
                $user->storeAvatar($request->file('data.attributes.avatar'));
            } elseif ($request->input('data.attributes.avatar') === null) {
                $user->deleteAvatar();
            }
        }

        return $user->toResource();
    }

    public function destroy(User $user)
    {
        //
    }
}
