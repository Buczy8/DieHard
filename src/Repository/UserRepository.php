<?php

namespace App\Repository;

use App\Models\User;
use PDO;

class UserRepository extends Repository
{
    public function getUserByEmail(string $email): ?User
    {
        $stmt = $this->database->prepare(
            "SELECT * FROM users WHERE email = :email"
        );
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }
        return User::fromArray($user);
    }

    public function getUserById(int $id): ?User
    {
        $stmt = $this->database->prepare(
            "SELECT * FROM users WHERE id = :id"
        );
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }
        return User::fromArray($user);
    }

    public function createUser(User $user): void
    {
        $stmt = $this->database->prepare(
            "INSERT INTO users (username, email, password, role) 
             VALUES (:username, :email, :password, :role)"
        );

        $stmt->execute([
            ':username' => $user->username,
            ':email' => $user->email,
            ':password' => $user->password,
            ':role' => $user->role
        ]);
    }
}