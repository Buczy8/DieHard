<?php

namespace App\DTO;

readonly class CreateUserDTO
{
    public function __construct(
        public string  $email,
        public string  $username,
        public string  $password,
        public string $role = 'user',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            username: $data['username'],
            password: $data['password'],
            role: $data['role'] ?? 'user'
        );
    }
}