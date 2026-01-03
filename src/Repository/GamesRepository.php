<?php

namespace App\Repository;

use PDO;
use App\Models\Game;
class GamesRepository extends Repository
{
    public function saveGame(int $userId, int $score, string $result, string $opponentName = 'Bot'): void
    {
        $stmt = $this->database->prepare("
            INSERT INTO games (user_id, score, opponent_name, result, played_at)
            VALUES (:user_id, :score, :opponent_name, :result, NOW())
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':score' => $score,
            ':opponent_name' => $opponentName,
            ':result' => $result
        ]);
    }

    public function getGamesByUserId(int $userId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->database->prepare("
            SELECT * FROM games 
            WHERE user_id = :user_id
            ORDER BY played_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rawGames = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $gamesCollection = [];

        foreach ($rawGames as $row) {
            $gamesCollection[] = Game::fromArray($row);
        }

        return $gamesCollection;
    }

    public function countGamesByUserId(int $userId): int
    {
        $stmt = $this->database->prepare("
            SELECT COUNT(*) FROM games WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}