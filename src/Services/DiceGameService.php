<?php

namespace App\Services;

class DiceGameService
{
    private array $dice = [0, 0, 0, 0, 0];
    private int $rollsLeft = 3;
    private array $scorecard = [];
    private array $computerScorecard = [];
    private array $possibleScores = [];
    private string $difficulty = 'medium';
    private BotStrategyInterface $botStrategy;

    private const CATEGORIES = [
        'score-aces', 'score-twos', 'score-threes', 'score-fours', 'score-fives', 'score-sixes',
        'score-three-of-a-kind', 'score-four-of-a-kind', 'score-full-house',
        'score-small-straight', 'score-large-straight', 'score-yahtzee', 'score-chance'
    ];

    public function __construct(array $state = null, string $difficulty = 'medium')
    {
        if ($state) {
            $this->hydrateState($state);
        } else {
            $this->initializeNewGame($difficulty);
        }
        $this->setBotStrategy($this->difficulty);
    }

    private function setBotStrategy(string $difficulty): void
    {
        $this->botStrategy = match ($difficulty) {
            'easy' => new EasyBotStrategy(),
            'hard' => new HardBotStrategy(),
            default => new MediumBotStrategy(),
        };
    }

    private function hydrateState(array $state): void
    {
        $this->dice = $state['dice'];
        $this->rollsLeft = $state['rollsLeft'];
        $this->scorecard = $state['scorecard'];
        $this->difficulty = $state['difficulty'] ?? 'medium';
        $this->computerScorecard = $state['computerScorecard'] ?? [];

        if (empty($this->computerScorecard)) {
            foreach (self::CATEGORIES as $cat) {
                $this->computerScorecard[$cat] = null;
            }
        }
        $this->calculatePossibleScores();
    }

    private function initializeNewGame(string $difficulty): void
    {
        $this->difficulty = $difficulty;
        foreach (self::CATEGORIES as $cat) {
            $this->scorecard[$cat] = null;
            $this->computerScorecard[$cat] = null;
        }
    }

    public function getState(): array
    {
        return [
            'dice' => $this->dice,
            'rollsLeft' => $this->rollsLeft,
            'scorecard' => $this->scorecard,
            'computerScorecard' => $this->computerScorecard,
            'playerTotals' => $this->calculateScorecardTotals($this->scorecard),
            'computerTotals' => $this->calculateScorecardTotals($this->computerScorecard),
            'possibleScores' => $this->possibleScores,
            'gameOver' => $this->isGameOver(),
            'difficulty' => $this->difficulty
        ];
    }


    public function startNewTurn(): void
    {
        $this->rollsLeft = 3;
        $this->dice = [0, 0, 0, 0, 0];
    }

    public function roll(array $heldIndices): void
    {
        if ($this->rollsLeft <= 0) return;

        $this->rollDiceIndices($heldIndices);
        $this->rollsLeft--;
        $this->calculatePossibleScores();
    }

    private function rollDiceIndices(array $heldIndices): void
    {
        for ($i = 0; $i < 5; $i++) {
            if (!in_array($i, $heldIndices)) {
                $this->dice[$i] = rand(1, 6);
            }
        }
    }

    public function selectCategory(string $categoryId): bool
    {
        if (!array_key_exists($categoryId, $this->scorecard) || $this->scorecard[$categoryId] !== null) {
            return false;
        }
        $this->scorecard[$categoryId] = $this->possibleScores[$categoryId] ?? 0;
        return true;
    }


    public function playComputerTurn(): array
    {
        $steps = [];
        $this->startNewTurn();

        $steps = array_merge($steps, $this->executeComputerRollPhase());

        $selectionResult = $this->finalizeComputerTurn();
        $steps[] = $selectionResult;

        $this->startNewTurn();

        return $steps;
    }


    private function executeComputerRollPhase(): array
    {
        $steps = [];
        $heldIndices = [];

        for ($rollNum = 1; $rollNum <= 3; $rollNum++) {
            $this->rollDiceIndices($heldIndices);
            $this->calculatePossibleScores();

            $steps[] = [
                'type' => 'roll',
                'dice' => $this->dice,
                'rollNumber' => $rollNum,
                'potential' => $this->possibleScores
            ];

            if ($rollNum === 3) break;

            $heldIndices = $this->botStrategy->determineHolds($this->dice, $this->computerScorecard);
            $steps[] = ['type' => 'hold', 'heldIndices' => $heldIndices];

            if (count($heldIndices) === 5) break;
        }

        return $steps;
    }

    private function finalizeComputerTurn(): array
    {
        $this->calculatePossibleScores();

        $bestCategory = $this->botStrategy->decideCategory($this->possibleScores, $this->computerScorecard);
        $points = 0;

        if ($bestCategory) {
            $points = $this->possibleScores[$bestCategory] ?? 0;
            if ($this->possibleScores[$bestCategory] === null) $points = 0;

            $this->computerScorecard[$bestCategory] = $points;
        }

        $compTotal = 0;
        foreach ($this->computerScorecard as $v) if ($v !== null) $compTotal += $v;

        return [
            'type' => 'finish',
            'category' => $bestCategory,
            'score' => $points,
            'total' => $compTotal
        ];
    }

    private function calculatePossibleScores(): void
    {
        $counts = array_count_values($this->dice);
        $sum = array_sum($this->dice);

        $this->possibleScores['score-aces'] = ($counts[1] ?? 0) * 1;
        $this->possibleScores['score-twos'] = ($counts[2] ?? 0) * 2;
        $this->possibleScores['score-threes'] = ($counts[3] ?? 0) * 3;
        $this->possibleScores['score-fours'] = ($counts[4] ?? 0) * 4;
        $this->possibleScores['score-fives'] = ($counts[5] ?? 0) * 5;
        $this->possibleScores['score-sixes'] = ($counts[6] ?? 0) * 6;

        $has3 = false;
        $has4 = false;
        $has5 = false;
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

        $this->calculateStraights();
    }

    private function calculateStraights(): void
    {
        $uniqueDice = array_unique($this->dice);
        sort($uniqueDice);
        $diceStr = implode('', $uniqueDice);

        $smallStraights = ['1234', '2345', '3456'];
        $largeStraights = ['12345', '23456'];

        $isSmall = false;
        foreach ($smallStraights as $s) if (str_contains($diceStr, $s)) $isSmall = true;

        $isLarge = false;
        foreach ($largeStraights as $l) if (str_contains($diceStr, $l)) $isLarge = true;

        $this->possibleScores['score-small-straight'] = $isSmall ? 30 : 0;
        $this->possibleScores['score-large-straight'] = $isLarge ? 40 : 0;
    }

    private function calculateScorecardTotals(array $targetScorecard): array
    {
        $upperKeys = ['score-aces', 'score-twos', 'score-threes', 'score-fours', 'score-fives', 'score-sixes'];
        $upperTotal = 0;
        $lowerTotal = 0;

        foreach ($targetScorecard as $key => $val) {
            if ($val === null) continue;

            if (in_array($key, $upperKeys)) {
                $upperTotal += $val;
            } else {
                $lowerTotal += $val;
            }
        }

        $bonus = ($upperTotal >= 63) ? 35 : 0;

        return [
            'upper' => $upperTotal,
            'lower' => $lowerTotal,
            'grand' => $upperTotal + $bonus + $lowerTotal
        ];
    }

    private function isGameOver(): bool
    {
        $pCount = count(array_filter($this->scorecard, fn($v) => $v !== null));
        $cCount = count(array_filter($this->computerScorecard, fn($v) => $v !== null));
        return $pCount === 13 && $cCount === 13;
    }
}
