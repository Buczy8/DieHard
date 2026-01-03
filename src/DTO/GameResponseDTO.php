<?php

namespace App\DTO;

use App\Models\Game;
use DateTime;

readonly class GameResponseDTO
{
    public function __construct(
        public int $score,
        public string $opponentName,
        public string $opponentInitials,
        public string $resultLabel, // np. "Win", "Loss"
        public string $resultBadgeClass, // np. "badge-win"
        public string $playedAtFormatted // np. "Jan 03, 2026 18:30"
    ) {}

    public static function fromEntity(Game $game): self
    {
        // 1. Formatowanie daty
        $date = new DateTime($game->playedAt);
        $formattedDate = $date->format('M d, Y H:i');

        // 2. Inicjały (pierwsze 2 litery, wielkie)
        $initials = strtoupper(substr($game->opponentName, 0, 2));

        // 3. Logika wyświetlania wyniku (CSS i tekst)
        $badgeClass = match ($game->result) {
            'win' => 'badge-win',
            'loss' => 'badge-loss',
            'draw' => 'badge-warning', // opcjonalnie
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