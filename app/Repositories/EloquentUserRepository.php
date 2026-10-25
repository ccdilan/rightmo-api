<?php

namespace App\Repositories;

use App\Contracts\UserRepository;
use App\Models\User;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
