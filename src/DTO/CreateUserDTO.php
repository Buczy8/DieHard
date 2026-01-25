<?php

namespace App\DTO;

readonly class CreateUserDTO
{
    public function __construct(
        public string $email,
        public string $username,
        public string $password,
        public string $role = 'user',
    )
    {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            username: $data['username'],
            password: $data['password'],
            role: $data['role'] ?? 'user'
        );
    }

    public static function validate(array $data): ?string
    {
        if (empty($data['email']) || empty($data['password']) || empty($data['username']) || empty($data['passwordConfirmation'])) {
            return "Fill all fields.";
        }

        if (strlen($data['email']) > 100) {
            return "Email is too long";
        }

        if (strlen($data['password']) < 8) {
            return "Password is too weak";
        }

        if ($data['password'] !== $data['passwordConfirmation']) {
            return "passwords does not match";
        }

        return null;
    }
}
