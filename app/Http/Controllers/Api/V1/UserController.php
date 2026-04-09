<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UserStoreRequest;
use App\Http\Resources\Api\v1\UserCollection;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): UserCollection
    {
        return new UserCollection(User::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): UserResource
    {
        if ($request->hasFile('data.attributes.avatar')) {
            $request->addAttributes(
                $this->uploadAvatar($request->file('data.attributes.avatar'))
            );
        }

        $user = User::create($request->mappedAttributes())
            ->syncRelationships($request->mappedRelationships());

        return new UserResource($user->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
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

    private function uploadAvatar(UploadedFile $avatar): array
    {
        $thumbnail = (new ImageManager(Driver::class))
            ->decode($avatar)
            ->cover(144, 144)
            ->encode(new WebpEncoder(quality: 90));

        $hashedName = pathinfo($avatar->hashName(), PATHINFO_FILENAME);
        $avatarName = "{$hashedName}.{$avatar->extension()}";
        $avatarPath = User::PATH_AVATAR . "/{$avatarName}";
        $thumbPath = User::PATH_AVATAR_THUMBS . "/{$hashedName}.webp";

        Storage::disk('public')->putFileAs(User::PATH_AVATAR, $avatar, $avatarName);
        Storage::disk('public')->put($thumbPath, $thumbnail);

        return [
            'avatar' => $avatarPath,
            'avatar_thumb' => $thumbPath,
        ];
    }
}
