<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\TokenResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{
    /**
     * Get the currently authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return $request->user()->toResource();
    }

    /**
     * Authenticate a user and issue a personal access token.
     */
    public function login(LoginRequest $request): JsonResponse|TokenResource
    {
        if (! Auth::attempt($request->credentials())) {
            return $this->unauthorized(__('api.401.invalid_credentials'));
        }

        $token = $request->user()->createToken('api');

        return new TokenResource($token);
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Log out the user from all devices (revoke all tokens).
     */
    public function logoutAll(Request $request): Response
    {
        $request->user()->tokens()->delete();

        return response()->noContent();
    }
}
