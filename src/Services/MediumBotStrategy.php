<?php

namespace App\Services;

class MediumBotStrategy implements BotStrategyInterface
{
    private EasyBotStrategy $easyStrategy;

    public function __construct()
    {
        $this->easyStrategy = new EasyBotStrategy();
    }

    public function determineHolds(array $dice, array $computerScorecard): array
    {
        if (rand(1, 10) <= 3) {
            return $this->easyStrategy->determineHolds($dice, $computerScorecard);
        }
        return $this->getStandardHeldIndices($dice, $computerScorecard);
    }

    private function getStandardHeldIndices(array $dice, array $computerScorecard): array
    {
        $counts = array_count_values($dice);
        arsort($counts);
        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);

        if ($maxCount === 5) return [0, 1, 2, 3, 4];

        if ($maxCount >= 4) {
            return array_keys(array_filter($dice, fn($v) => $v == $mostFrequentVal));
        }

        $straightIndices = $this->checkStandardStraightStrategy($dice, $computerScorecard);
        if (!empty($straightIndices)) return $straightIndices;

        if ($maxCount >= 2 || ($maxCount == 1 && $mostFrequentVal >= 4)) {
            return array_keys(array_filter($dice, fn($v) => $v == $mostFrequentVal));
        }

        $maxVal = max($dice);
        return array_keys(array_filter($dice, fn($v) => $v == $maxVal));
    }

    private function checkStandardStraightStrategy(array $dice, array $computerScorecard): array
    {
        $needsLg = ($computerScorecard['score-large-straight'] ?? null) === null;
        $needsSm = ($computerScorecard['score-small-straight'] ?? null) === null;

        if (!$needsLg && !$needsSm) return [];

        $uniqueDice = array_unique($dice);
        $straightPatterns = ['1234', '2345', '3456'];

        foreach ($straightPatterns as $pattern) {
            $matchingValues = array_intersect(str_split($pattern), $uniqueDice);
            $matches = count($matchingValues);

            if (($matches >= 4 && $needsLg) || ($matches >= 3 && $needsSm)) {
                $indicesToHold = [];
                foreach ($dice as $idx => $val) {
                    if (in_array($val, $matchingValues) && !in_array($idx, $indicesToHold)) {
                        $indicesToHold[] = $idx;
                    }
                }
                if (count($indicesToHold) >= 3) return $indicesToHold;
            }
        }
        return [];
    }

    public function decideCategory(array $possibleScores, array $computerScorecard): ?string
    {
        $cat = $this->findGreedyCategory($possibleScores, $computerScorecard);
        return $cat ?: $this->findStandardFallbackCategory($possibleScores, $computerScorecard);
    }

    private function findGreedyCategory(array $possibleScores, array $computerScorecard): ?string
    {
        if (($possibleScores['score-yahtzee'] ?? 0) == 50 && ($computerScorecard['score-yahtzee'] ?? null) === null) {
            return 'score-yahtzee';
        }

        $bestCategory = null;
        $maxPoints = -1;

        foreach ($possibleScores as $cat => $points) {
            if (($computerScorecard[$cat] ?? null) !== null) continue;

            if ($cat === 'score-chance' && $points < 20) continue;

            if ($points >= $maxPoints) {
                $maxPoints = $points;
                $bestCategory = $cat;
            }
        }
        return $bestCategory;
    }

    private function findStandardFallbackCategory(array $possibleScores, array $computerScorecard): ?string
    {
        $scratchOrder = ['score-aces', 'score-twos', 'score-yahtzee', 'score-four-of-a-kind', 'score-large-straight'];
        foreach ($scratchOrder as $cat) {
            if (($computerScorecard[$cat] ?? null) === null) return $cat;
        }
        foreach ($possibleScores as $cat => $points) {
            if (($computerScorecard[$cat] ?? null) === null) return $cat;
        }
        return null;
    }
}
