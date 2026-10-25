<?php

namespace App\Services;

use App\Contracts\UserRepository;
use App\Data\AuthSession;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Hashing\Hasher;

final class AuthService
{
    public function __construct(private UserRepository $users, private Hasher $hasher) {}

    public function login(string $email, string $password): AuthSession
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || ! $this->hasher->check($password, $user->password)) {
            throw new AuthenticationException('The email or password is incorrect.');
        }
        $expiresAt = now()->addHours(8);
        $token = $user->createToken('rightmo-web', ['*'], $expiresAt);

        return new AuthSession($user, $token->plainTextToken, $expiresAt);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
