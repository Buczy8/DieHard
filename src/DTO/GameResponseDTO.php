<?php

namespace App\DTO;

use App\Models\Game;
use DateTime;

readonly class GameResponseDTO
{
    public function __construct(
        public int    $score,
        public string $opponentName,
        public string $opponentInitials,
        public string $resultLabel,
        public string $resultBadgeClass,
        public string $playedAtFormatted
    )
    {
    }

    public static function fromEntity(Game $game): self
    {

        $date = new DateTime($game->playedAt);
        $formattedDate = $date->format('M d, Y H:i');


        $initials = strtoupper(substr($game->opponentName, 0, 2));


        $badgeClass = match ($game->result) {
            'win' => 'badge-win',
            'loss' => 'badge-loss',
            'draw' => 'badge-warning',
            default => 'badge-secondary'
        };

        return new self(
            score: $game->score,
            opponentName: $game->opponentName,
            opponentInitials: $initials,
            resultLabel: ucfirst($game->result),
            resultBadgeClass: $badgeClass,
            playedAtFormatted: $formattedDate
        );
    }
}