<?php

namespace App\Contracts;

use App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
}
