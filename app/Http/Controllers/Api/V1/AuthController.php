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

/**
 * @group Authentication
 *
 * Issue, inspect, and revoke eight-hour Sanctum bearer tokens.
 */
class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    /**
     * Log in
     *
     * Returns a new token. Invalid credentials return 401. Login is limited to five attempts per email/IP and 60 per IP each minute.
     *
     * @unauthenticated
     *
     * @responseFile 200 docs/responses/login.json
     *
     * @response 401 {"message":"The email or password is incorrect."}
     * @response 422 {"message":"The email field must be a valid email address.","errors":{"email":["The email field must be a valid email address."]}}
     * @response 429 {"message":"Too many requests. Please try again shortly."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login($request->string('email')->toString(), $request->string('password')->toString());

        return response()->json(['data' => ['user' => new UserResource($session->user), 'token' => $session->token, 'token_type' => 'Bearer', 'expires_at' => $session->expiresAt->format(DATE_ATOM)]])->header('Cache-Control', 'no-store');
    }

    /**
     * Get the current user
     *
     * Returns the user associated with the supplied token.
     *
     * @responseFile 200 docs/responses/user.json
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Log out
     *
     * Revokes only the supplied token. Other active tokens remain valid.
     *
     * @response 204
     * @response 401 {"message":"Unauthenticated."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->auth->logout($user);

        return response()->noContent();
    }
}
