<?php
namespace App\Models;
use DateTimeImmutable;

class User
{
    public function __construct(
        public string  $email,
        public string  $username,
        public string  $password,

        public ?int $id = null,
        public string  $role = 'user',
        public ?string $avatar = null,
        public ?DateTimeImmutable $createdAt = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            email: $data['email'],
            username: $data['username'],
            password: $data['password'],
            role: $data['role'],
            avatar: $data['avatar'] ?? null,
            createdAt: isset($data['created_at'])
                ? new DateTimeImmutable($data['created_at'])
                : null
        );
    }
}