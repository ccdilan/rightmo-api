<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login($request->string('email')->toString(), $request->string('password')->toString());

        return response()->json(['data' => ['user' => new UserResource($session->user), 'token' => $session->token, 'token_type' => 'Bearer', 'expires_at' => $session->expiresAt->format(DATE_ATOM)]])->header('Cache-Control', 'no-store');
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->auth->logout($user);

        return response()->noContent();
    }
}
