<?php

namespace App\Services;

interface BotStrategyInterface
{
    public function determineHolds(array $dice, array $computerScorecard): array;
    public function decideCategory(array $possibleScores, array $computerScorecard): ?string;
}
