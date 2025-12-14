<?php

namespace App\DTO;

use App\Models\User;

readonly class UserResponseDTO
{
    public function __construct(
        public int    $id,
        public string $email,
        public string $username,
        public string $role,
        public string $createdAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id:(int) $user->id,
            email: $user->email,
            username: $user->username,
            role: $user->role ?? 'user',
            createdAt: $user->createdAt ?? date('Y-m-d H:i:s')
        );
    }
}