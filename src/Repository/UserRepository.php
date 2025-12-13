<?php
namespace App\Repository;

use PDO;
class UserRepository extends Repository{
    public function getUser(): ?array {

        $stmt = $this->database->prepare(
            "SELECT * FROM users"
        );
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tutaj mapujemy w przyszłości tablicę na obiekt User
        return $users;
    }
}