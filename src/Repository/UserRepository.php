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
    public function getUserByUserName(string $username): ?User
    {
        $stmt = $this->database->prepare(
            "SELECT * FROM users WHERE username = :username"
        );
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
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

    public function updateUser(User $user): void
    {
        $stmt = $this->database->prepare(
            "UPDATE users SET username = :username, password = :password,avatar = :avatar  WHERE id = :id"
        );

        $stmt->execute([
            ':username' => $user->username,
            ':password' => $user->password,
            ':avatar' => $user->avatar,
            ':id' => $user->id
        ]);
    }

    public function getAllUsers(): array {
        $stmt = $this->database->prepare('
        SELECT id, email, username, role FROM users ORDER BY id ASC
    ');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deleteUser(int $id): void {
        $stmt = $this->database->prepare('
        DELETE FROM users WHERE id = :id
    ');
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }
}