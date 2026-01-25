<?php

namespace App\Models;


class Game
{
    public function __construct(
        public int    $id,
        public int    $userId,
        public int    $score,
        public string $opponentName,
        public string $result,
        public string $playedAt
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            score: (int)$data['score'],
            opponentName: $data['opponent_name'],
            result: $data['result'],
            playedAt: $data['played_at']
        );
    }
}