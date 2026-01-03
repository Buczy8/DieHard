<?php

namespace App\Models;

class UserStatistics
{
    public function __construct(
        public int $gamesPlayed,
        public int $gamesWon,
        public int $highScore,
        public ?int $userId = null,
        public ?int $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            gamesPlayed: $data['games_played'] ?? 0,
            gamesWon: $data['games_won'] ?? 0,
            highScore: $data['high_score'] ?? 0,
            userId: isset($data['user_id']) ? (int)$data['user_id'] : null,
            id: isset($data['id']) ? (int)$data['id'] : null
        );
    }
}