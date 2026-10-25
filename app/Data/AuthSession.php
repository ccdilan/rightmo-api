<?php

namespace App\Data;

use App\Models\User;
use DateTimeInterface;

final readonly class AuthSession
{
    public function __construct(public User $user, public string $token, public DateTimeInterface $expiresAt) {}
}
