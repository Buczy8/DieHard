<?php
namespace App\Models;
class User
{
    public function __construct(
        public int     $id,
        public string  $email,
        public string  $username,
        public string  $password,
        public string  $role,
        public ?string $createdAt = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            email: $data['email'],
            username: $data['username'],
            password: $data['password'],
            role: $data['role'],
            createdAt: $data['created_at'] ?? null
        );
    }
}