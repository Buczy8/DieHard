<?php

namespace App\Repository;

use PDO;
use App\Models\UserStatistics;

class UserStatisticsRepository extends Repository
{
    public function getStatsByUserEmail(string $email): UserStatistics
    {
        $stmt = $this->database->prepare(
            "SELECT us.* FROM user_statistics us
             JOIN users u ON u.id = us.user_id
             WHERE u.email = :email"
        );

        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return new UserStatistics(0, 0, 0);
        }

        return UserStatistics::fromArray($data);
    }

    public function getLeaderboard(int $limit = 5): array
    {

        $stmt = $this->database->prepare("SELECT * FROM v_user_leaderboard LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}