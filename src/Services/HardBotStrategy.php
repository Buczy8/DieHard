<?php

namespace App\Services;

class HardBotStrategy implements BotStrategyInterface
{
    public function determineHolds(array $dice, array $computerScorecard): array
    {
        $counts = array_count_values($dice);
        arsort($counts);

        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);
        $uniqueDice = array_unique($dice);
        sort($uniqueDice);

        if ($maxCount === 5) return [0, 1, 2, 3, 4];

        if ($maxCount >= 4) {
            return array_keys(array_filter($dice, fn($v) => $v == $mostFrequentVal));
        }

        $needsLg = ($computerScorecard['score-large-straight'] ?? null) === null;
        $needsSm = ($computerScorecard['score-small-straight'] ?? null) === null;

        if ($needsLg || $needsSm) {
            $longestRun = [];
            $currentRun = [$uniqueDice[0] ?? 0];

            for ($i = 0; $i < count($uniqueDice) - 1; $i++) {
                if ($uniqueDice[$i + 1] == $uniqueDice[$i] + 1) {
                    $currentRun[] = $uniqueDice[$i + 1];
                } else {
                    if (count($currentRun) > count($longestRun)) $longestRun = $currentRun;
                    $currentRun = [$uniqueDice[$i + 1]];
                }
            }
            if (count($currentRun) > count($longestRun)) $longestRun = $currentRun;

            if (count($longestRun) >= 4 && $needsLg) {
                return $this->getIndicesForValues($dice, $longestRun);
            }

            if (count($longestRun) >= 3 && $needsSm && $maxCount < 3) {
                return $this->getIndicesForValues($dice, $longestRun);
            }
        }

        if (($computerScorecard['score-full-house'] ?? null) === null) {
            if ($maxCount === 2 && count($counts) === 3) {
                $pairs = array_keys(array_filter($counts, fn($c) => $c === 2));
                return array_keys(array_filter($dice, fn($v) => in_array($v, $pairs)));
            }
        }

        return array_keys(array_filter($dice, fn($v) => $v == $mostFrequentVal));
    }

    private function getIndicesForValues(array $dice, array $valuesToKeep): array
    {
        $indices = [];
        $tempDice = $dice;
        foreach ($valuesToKeep as $val) {
            $key = array_search($val, $tempDice);
            if ($key !== false) {
                $indices[] = $key;
                unset($tempDice[$key]);
            }
        }
        return $indices;
    }

    public function decideCategory(array $possibleScores, array $computerScorecard): ?string
    {
        if (($possibleScores['score-yahtzee'] ?? 0) === 50 && ($computerScorecard['score-yahtzee'] ?? null) === null) {
            return 'score-yahtzee';
        }

        foreach ([6, 5, 4, 3, 2, 1] as $dieVal) {
            $catKey = $this->getCategoryKeyForDie($dieVal);
            if (($computerScorecard[$catKey] ?? null) !== null) continue;

            $points = $possibleScores[$catKey] ?? 0;
            $count = ($points > 0) ? ($points / $dieVal) : 0;

            if ($count >= 4) return $catKey;

            if ($count == 3) {
                if ($dieVal <= 2 && ($possibleScores['score-full-house'] ?? 0) === 25 && ($computerScorecard['score-full-house'] ?? null) === null) {
                    return 'score-full-house';
                }
                return $catKey;
            }
        }

        if (($possibleScores['score-large-straight'] ?? 0) === 40 && ($computerScorecard['score-large-straight'] ?? null) === null) {
            return 'score-large-straight';
        }
        if (($possibleScores['score-small-straight'] ?? 0) === 30 && ($computerScorecard['score-small-straight'] ?? null) === null) {
            return 'score-small-straight';
        }
        if (($possibleScores['score-full-house'] ?? 0) === 25 && ($computerScorecard['score-full-house'] ?? null) === null) {
            return 'score-full-house';
        }

        // We need dice values to calculate sum, but we only have possibleScores here.
        // However, chance score is exactly the sum.
        $sum = $possibleScores['score-chance'] ?? 0;
        
        if (($computerScorecard['score-four-of-a-kind'] ?? null) === null && ($possibleScores['score-four-of-a-kind'] ?? 0) > 0) {
            if ($sum >= 20) return 'score-four-of-a-kind';
        }
        if (($computerScorecard['score-three-of-a-kind'] ?? null) === null && ($possibleScores['score-three-of-a-kind'] ?? 0) > 0) {
            if ($sum >= 24) return 'score-three-of-a-kind';
        }

        if (($computerScorecard['score-chance'] ?? null) === null && $sum >= 22) {
            return 'score-chance';
        }

        foreach ([6, 5, 4] as $dieVal) {
            $catKey = $this->getCategoryKeyForDie($dieVal);
            if (($computerScorecard[$catKey] ?? null) === null) {
                if (($possibleScores[$catKey] ?? 0) >= ($dieVal * 2)) return $catKey;
            }
        }

        return $this->findSmartScratchCategory($possibleScores, $computerScorecard);
    }

    private function findSmartScratchCategory(array $possibleScores, array $computerScorecard): string
    {
        $priorities = [
            'score-aces',
            'score-twos',
            'score-yahtzee',
            'score-four-of-a-kind',
            'score-large-straight'
        ];

        foreach ($priorities as $cat) {
            if (($computerScorecard[$cat] ?? null) === null) return $cat;
        }

        foreach ($possibleScores as $cat => $points) {
            if (($computerScorecard[$cat] ?? null) === null) return $cat;
        }
        return 'score-chance'; // Fallback
    }

    private function getCategoryKeyForDie(int $val): string
    {
        return match ($val) {
            1 => 'score-aces',
            2 => 'score-twos',
            3 => 'score-threes',
            4 => 'score-fours',
            5 => 'score-fives',
            6 => 'score-sixes',
        };
    }
}
