<?php

namespace App\Services;

class EasyBotStrategy implements BotStrategyInterface
{
    public function determineHolds(array $dice, array $computerScorecard): array
    {
        $counts = array_count_values($dice);
        arsort($counts);

        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);

        if ($maxCount >= 2) {
            return array_keys(array_filter($dice, fn($v) => $v == $mostFrequentVal));
        }

        $sixes = array_keys(array_filter($dice, fn($v) => $v == 6));
        if (!empty($sixes)) {
            return $sixes;
        }

        return [];
    }

    public function decideCategory(array $possibleScores, array $computerScorecard): ?string
    {
        $bestCategory = null;
        $maxPoints = -1;

        foreach ($possibleScores as $cat => $points) {
            if (($computerScorecard[$cat] ?? null) !== null) continue;

            if ($points > $maxPoints) {
                $maxPoints = $points;
                $bestCategory = $cat;
            }
        }

        if ($maxPoints > 0) {
            return $bestCategory;
        }

        foreach ($possibleScores as $cat => $points) {
            if (($computerScorecard[$cat] ?? null) === null) return $cat;
        }
        return null;
    }
}
