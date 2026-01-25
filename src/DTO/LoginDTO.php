<?php

namespace App\DTO;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    )
    {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? ''
        );
    }

    public static function validate(self $dto): ?string
    {
        if (empty($dto->email) || empty($dto->password)) {
            return "Fill all fields";
        }

        if (strlen($dto->email) > 100) {
            return "Email is too long";
        }

        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }

        return null;
    }
}
