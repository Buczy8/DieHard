<?php

namespace App\DTO;

use App\Models\UserStatistics;

readonly class UserStatisticsResponseDTO
{
    public function __construct(
        public int   $gamesPlayed,
        public int   $gamesWon,
        public int   $highScore,
        public float $winRate
    )
    {
    }

    public static function fromEntity(UserStatistics $stats): self
    {
        $winRate = $stats->gamesPlayed > 0
            ? round(($stats->gamesWon / $stats->gamesPlayed) * 100, 2)
            : 0;

        return new self(
            gamesPlayed: $stats->gamesPlayed,
            gamesWon: $stats->gamesWon,
            highScore: $stats->highScore,
            winRate: $winRate
        );
    }
}