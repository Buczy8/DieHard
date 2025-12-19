<?php
namespace App\Services;
class DiceGameService {
    private array $dice = [0, 0, 0, 0, 0];
    private int $rollsLeft = 3;
    private array $scorecard = [];
    private array $computerScorecard = [];
    private array $possibleScores = [];

    public function __construct(array $state = null) {
        $categories = [
            'score-aces', 'score-twos', 'score-threes', 'score-fours', 'score-fives', 'score-sixes',
            'score-three-of-a-kind', 'score-four-of-a-kind', 'score-full-house',
            'score-small-straight', 'score-large-straight', 'score-yahtzee', 'score-chance'
        ];

        if ($state) {
            $this->dice = $state['dice'];
            $this->rollsLeft = $state['rollsLeft'];
            $this->scorecard = $state['scorecard'];

            $this->computerScorecard = $state['computerScorecard'] ?? [];
            if (empty($this->computerScorecard)) {
                foreach ($categories as $cat) {
                    $this->computerScorecard[$cat] = null;
                }
            }

            $this->calculatePossibleScores();
        } else {
            foreach ($categories as $cat) {
                $this->scorecard[$cat] = null;
                $this->computerScorecard[$cat] = null;
            }
        }
    }

    public function startNewTurn() {
        $this->rollsLeft = 3;
        $this->dice = [0, 0, 0, 0, 0];
    }

    public function roll(array $heldIndices) {
        if ($this->rollsLeft <= 0) return;

        for ($i = 0; $i < 5; $i++) {
            if (!in_array($i, $heldIndices)) {
                $this->dice[$i] = rand(1, 6);
            }
        }
        $this->rollsLeft--;
        $this->calculatePossibleScores();
    }

    public function selectCategory(string $categoryId): bool {
        if (!array_key_exists($categoryId, $this->scorecard) || $this->scorecard[$categoryId] !== null) {
            return false;
        }
        $this->scorecard[$categoryId] = $this->possibleScores[$categoryId] ?? 0;
        return true;
    }

    public function playComputerTurn(): array {
        $steps = [];
        $this->rollsLeft = 3;
        $this->dice = [0, 0, 0, 0, 0];

        $needsYahtzee = $this->computerScorecard['score-yahtzee'] === null;
        $needsLgStraight = $this->computerScorecard['score-large-straight'] === null;
        $needsSmStraight = $this->computerScorecard['score-small-straight'] === null;

        $heldIndices = [];

        for ($rollNum = 1; $rollNum <= 3; $rollNum++) {
            for ($i = 0; $i < 5; $i++) {
                if (!in_array($i, $heldIndices)) {
                    $this->dice[$i] = rand(1, 6);
                }
            }

            $this->calculatePossibleScores();

            $steps[] = [
                'type' => 'roll',
                'dice' => $this->dice,
                'rollNumber' => $rollNum,
                'potential' => $this->possibleScores
            ];

            if ($rollNum === 3) break;

            $heldIndices = [];
            $diceValues = $this->dice;
            $counts = array_count_values($diceValues);
            arsort($counts);

            $mostFrequentVal = array_key_first($counts);
            $maxCount = reset($counts);

            if ($maxCount === 5) {
                $heldIndices = [0, 1, 2, 3, 4];
            }
            elseif ($maxCount >= 4) {
                foreach ($diceValues as $idx => $val) {
                    if ($val == $mostFrequentVal) $heldIndices[] = $idx;
                }
            }
            else {
                $foundStraightStrategy = false;

                if ($needsLgStraight || $needsSmStraight) {
                    $uniqueDice = array_unique($diceValues);
                    sort($uniqueDice);
                    $strDice = implode('', $uniqueDice);

                    $straightPatterns = ['1234', '2345', '3456'];

                    foreach ($straightPatterns as $pattern) {
                        $matches = 0;
                        $matchingValues = [];

                        $patternArr = str_split($pattern);
                        foreach ($patternArr as $pVal) {
                            if (in_array((int)$pVal, $uniqueDice)) {
                                $matches++;
                                $matchingValues[] = (int)$pVal;
                            }
                        }

                        if (($matches >= 4 && $needsLgStraight) || ($matches >= 3 && $needsSmStraight)) {
                            foreach ($diceValues as $idx => $val) {
                                if (in_array($val, $matchingValues)) {
                                    if (!in_array($idx, $heldIndices)) {
                                        $heldIndices[] = $idx;
                                    }
                                }
                            }

                            if (count($heldIndices) >= 3) {
                                $foundStraightStrategy = true;
                                break;
                            } else {
                                $heldIndices = [];
                            }
                        }
                    }
                }

                if (!$foundStraightStrategy) {
                    $mapNumToKey = [
                        1 => 'score-aces', 2 => 'score-twos', 3 => 'score-threes',
                        4 => 'score-fours', 5 => 'score-fives', 6 => 'score-sixes'
                    ];

                    $targetKey = $mapNumToKey[$mostFrequentVal];
                    $isUpperFilled = $this->computerScorecard[$targetKey] !== null;

                    if ($maxCount >= 2 || ($maxCount == 1 && $mostFrequentVal >= 4)) {
                        foreach ($diceValues as $idx => $val) {
                            if ($val == $mostFrequentVal) $heldIndices[] = $idx;
                        }
                    } else {
                        $maxVal = max($diceValues);
                        foreach ($diceValues as $idx => $val) {
                            if ($val == $maxVal) {
                                $heldIndices[] = $idx;
                                break;
                            }
                        }
                    }
                }
            }

            sort($heldIndices);
            $heldIndices = array_unique($heldIndices);

            $steps[] = ['type' => 'hold', 'heldIndices' => array_values($heldIndices)];

            if (count($heldIndices) === 5) break;
        }

        $this->calculatePossibleScores();

        $bestCategory = null;
        $maxPoints = -1;

        if ($this->possibleScores['score-yahtzee'] == 50 && $this->computerScorecard['score-yahtzee'] === null) {
            $bestCategory = 'score-yahtzee';
            $maxPoints = 50;
        }
        else {
            foreach ($this->possibleScores as $cat => $points) {
                if (($this->computerScorecard[$cat] ?? null) === null) {

                    if ($cat === 'score-chance' && $points < 20 && $rollNum < 10) {
                        continue;
                    }

                    if ($points >= $maxPoints) {
                        $maxPoints = $points;
                        $bestCategory = $cat;
                    }
                }
            }
        }

        if (!$bestCategory) {
            $scratchOrder = [
                'score-aces', 'score-twos', 'score-yahtzee',
                'score-four-of-a-kind', 'score-large-straight'
            ];

            foreach ($scratchOrder as $cat) {
                if (($this->computerScorecard[$cat] ?? null) === null) {
                    $bestCategory = $cat;
                    $maxPoints = 0;
                    break;
                }
            }

            if (!$bestCategory) {
                foreach ($this->computerScorecard as $cat => $val) {
                    if ($val === null) {
                        $bestCategory = $cat;
                        $maxPoints = 0;
                        break;
                    }
                }
            }
        }

        if ($bestCategory) {
            $this->computerScorecard[$bestCategory] = $maxPoints;
        }

        $compTotal = 0;
        foreach ($this->computerScorecard as $v) if($v !== null) $compTotal += $v;

        $steps[] = [
            'type' => 'finish',
            'category' => $bestCategory,
            'score' => $maxPoints,
            'total' => $compTotal
        ];

        $this->startNewTurn();

        return $steps;
    }

    public function getState(): array {
        $playerTotals = $this->calculateScorecardTotals($this->scorecard);
        $computerTotals = $this->calculateScorecardTotals($this->computerScorecard);

        return [
            'dice' => $this->dice,
            'rollsLeft' => $this->rollsLeft,
            'scorecard' => $this->scorecard,
            'computerScorecard' => $this->computerScorecard,
            'playerTotals' => $playerTotals,
            'computerTotals' => $computerTotals,
            'possibleScores' => $this->possibleScores,
            'gameOver' => $this->isGameOver()
        ];
    }

    private function calculatePossibleScores() {
        $counts = array_count_values($this->dice);
        $sum = array_sum($this->dice);

        $this->possibleScores['score-aces'] = ($counts[1] ?? 0) * 1;
        $this->possibleScores['score-twos'] = ($counts[2] ?? 0) * 2;
        $this->possibleScores['score-threes'] = ($counts[3] ?? 0) * 3;
        $this->possibleScores['score-fours'] = ($counts[4] ?? 0) * 4;
        $this->possibleScores['score-fives'] = ($counts[5] ?? 0) * 5;
        $this->possibleScores['score-sixes'] = ($counts[6] ?? 0) * 6;

        $has3 = false; $has4 = false; $has5 = false;
        foreach ($counts as $val) {
            if ($val >= 3) $has3 = true;
            if ($val >= 4) $has4 = true;
            if ($val == 5) $has5 = true;
        }
        $isFullHouse = (in_array(3, $counts) && in_array(2, $counts)) || in_array(5, $counts);

        $this->possibleScores['score-three-of-a-kind'] = $has3 ? $sum : 0;
        $this->possibleScores['score-four-of-a-kind'] = $has4 ? $sum : 0;
        $this->possibleScores['score-full-house'] = $isFullHouse ? 25 : 0;
        $this->possibleScores['score-yahtzee'] = $has5 ? 50 : 0;
        $this->possibleScores['score-chance'] = $sum;

        $uniqueDice = array_unique($this->dice);
        sort($uniqueDice);
        $diceStr = implode('', $uniqueDice);

        $smallStraights = ['1234', '2345', '3456'];
        $isSmall = false;
        foreach($smallStraights as $s) if (str_contains($diceStr, $s)) $isSmall = true;
        $this->possibleScores['score-small-straight'] = $isSmall ? 30 : 0;

        $largeStraights = ['12345', '23456'];
        $isLarge = false;
        foreach($largeStraights as $l) if (str_contains($diceStr, $l)) $isLarge = true;
        $this->possibleScores['score-large-straight'] = $isLarge ? 40 : 0;
    }

    private function calculateScorecardTotals(array $targetScorecard): array {
        $upperKeys = ['score-aces', 'score-twos', 'score-threes', 'score-fours', 'score-fives', 'score-sixes'];
        $upperTotal = 0;
        $lowerTotal = 0;

        foreach ($targetScorecard as $key => $val) {
            if ($val !== null) {
                if (in_array($key, $upperKeys)) $upperTotal += $val;
                else $lowerTotal += $val;
            }
        }
        $bonus = ($upperTotal >= 63) ? 35 : 0;
        return [
            'upper' => $upperTotal,
            'lower' => $lowerTotal,
            'grand' => $upperTotal + $bonus + $lowerTotal
        ];
    }

    private function isGameOver(): bool {
        foreach ($this->scorecard as $val) {
            if ($val === null) return false;
        }
        foreach ($this->computerScorecard as $val) {
            if ($val === null) return false;
        }
        return true;
    }
}