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
    public function updateStats(int $userId, int $score, bool $isWin): void
    {
        $winIncrement = $isWin ? 1 : 0;

        $sql = "
            INSERT INTO user_statistics (user_id, games_played, games_won, high_score, created_at, updated_at)
            VALUES (:user_id, 1, :win_inc, :score, NOW(), NOW())
            ON CONFLICT (user_id) DO UPDATE SET
                games_played = user_statistics.games_played + 1,
                games_won = user_statistics.games_won + EXCLUDED.games_won,
                high_score = GREATEST(user_statistics.high_score, EXCLUDED.high_score),
                updated_at = NOW()
        ";

        $stmt = $this->database->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':win_inc' => $winIncrement,
            ':score' => $score
        ]);
    }
}