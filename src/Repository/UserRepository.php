<?php
namespace App\Repository;

use App\Models\User;
use PDO;
class UserRepository extends Repository{
    public function getUserByEmail(string $email) {

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
}