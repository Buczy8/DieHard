<?php

namespace App\Repository;

use PDO;
use App\Models\Game;
class GamesRepository extends Repository
{
    public function getRecentGamesByUserEmail(string $email, int $limit = 5): array
    {
        $stmt = $this->database->prepare("
            SELECT g.* FROM games g
            JOIN users u ON u.id = g.user_id
            WHERE u.email = :email
            ORDER BY g.played_at DESC
            LIMIT :limit
        ");

        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rawGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gamesCollection = [];

        foreach ($rawGames as $row) {
            $gamesCollection[] = Game::fromArray($row);
        }

        return $gamesCollection;
    }
}