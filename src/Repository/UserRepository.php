<?php

namespace App\Repository;

use App\Models\User;
use PDO;

class UserRepository extends Repository
{
    public function getUserByEmail(string $email): ?User
    {
        $stmt = $this->database->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? User::fromArray($user) : null;
    }

    public function getUserByUserName(string $username): ?User
    {
        $stmt = $this->database->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? User::fromArray($user) : null;
    }

    public function getUserById(int $id): ?User
    {
        $stmt = $this->database->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? User::fromArray($user) : null;
    }

    public function createUser(User $user): void
    {
        $stmt = $this->database->prepare(
            "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)"
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
            "UPDATE users SET username = :username, password = :password, avatar = :avatar WHERE id = :id"
        );
        $stmt->execute([
            ':username' => $user->username,
            ':password' => $user->password,
            ':avatar' => $user->avatar,
            ':id' => $user->id
        ]);
    }

    public function updateUserRole(int $id, string $role): void
    {
        $stmt = $this->database->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getAllUsers(): array
    {
        $stmt = $this->database->prepare('SELECT id, email, username, role, avatar FROM users ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUsersPaginated(int $limit, int $offset, string $search = '', string $role = 'all', string $sortOrder = 'DESC'): array
    {
        $query = '
            SELECT 
                u.id, 
                u.email, 
                u.username, 
                u.role, 
                u.avatar,
                (SELECT COUNT(*) FROM games g WHERE g.user_id = u.id) as games_played
            FROM users u
            WHERE 1=1
        ';

        $params = [];

        if (!empty($search)) {
            $query .= ' AND (u.username ILIKE :search OR u.email ILIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($role !== 'all') {
            $query .= ' AND u.role = :role';
            $params[':role'] = $role;
        }

        $order = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY u.id $order";

        $query .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->database->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsersWithFilters(string $search = '', string $role = 'all'): int
    {
        $query = 'SELECT COUNT(*) FROM users u WHERE 1=1';
        $params = [];

        if (!empty($search)) {
            $query .= ' AND (u.username ILIKE :search OR u.email ILIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($role !== 'all') {
            $query .= ' AND u.role = :role';
            $params[':role'] = $role;
        }

        $stmt = $this->database->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countAllUsers(): int
    {
        $stmt = $this->database->prepare('SELECT COUNT(*) FROM users');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countAdmins(): int
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countActivePlayers(): int
    {
        $year = date('Y');
        $stmt = $this->database->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM games 
            WHERE EXTRACT(YEAR FROM played_at) = :year
        ");
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function deleteUser(int $id): void
    {
        $stmt = $this->database->prepare('DELETE FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }
}
