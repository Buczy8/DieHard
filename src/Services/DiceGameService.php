<?php

namespace App\Services;

class DiceGameService {
    private array $dice = [0, 0, 0, 0, 0];
    private int $rollsLeft = 3;
    private array $scorecard = [];
    private array $computerScorecard = [];
    private array $possibleScores = [];
    private string $difficulty = 'medium'; // 'easy', 'medium', 'hard'

    private const CATEGORIES = [
        'score-aces', 'score-twos', 'score-threes', 'score-fours', 'score-fives', 'score-sixes',
        'score-three-of-a-kind', 'score-four-of-a-kind', 'score-full-house',
        'score-small-straight', 'score-large-straight', 'score-yahtzee', 'score-chance'
    ];

    public function __construct(array $state = null, string $difficulty = 'medium') {
        if ($state) {
            $this->hydrateState($state);
        } else {
            $this->initializeNewGame($difficulty);
        }
    }

    // --- State Management ---

    private function hydrateState(array $state): void {
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

    private function initializeNewGame(string $difficulty): void {
        $this->difficulty = $difficulty;
        foreach (self::CATEGORIES as $cat) {
            $this->scorecard[$cat] = null;
            $this->computerScorecard[$cat] = null;
        }
    }

    public function getState(): array {
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

    // --- Core Game Logic ---

    public function startNewTurn(): void {
        $this->rollsLeft = 3;
        $this->dice = [0, 0, 0, 0, 0];
    }

    public function roll(array $heldIndices): void {
        if ($this->rollsLeft <= 0) return;

        $this->rollDiceIndices($heldIndices);
        $this->rollsLeft--;
        $this->calculatePossibleScores();
    }

    private function rollDiceIndices(array $heldIndices): void {
        for ($i = 0; $i < 5; $i++) {
            if (!in_array($i, $heldIndices)) {
                $this->dice[$i] = rand(1, 6);
            }
        }
    }

    public function selectCategory(string $categoryId): bool {
        if (!array_key_exists($categoryId, $this->scorecard) || $this->scorecard[$categoryId] !== null) {
            return false;
        }
        $this->scorecard[$categoryId] = $this->possibleScores[$categoryId] ?? 0;
        return true;
    }

    // --- Computer Logic (Coordinator) ---

    public function playComputerTurn(): array {
        $steps = [];
        $this->startNewTurn();

        // Phase 1: Rolling
        $steps = array_merge($steps, $this->executeComputerRollPhase());

        // Phase 2: Selection
        $selectionResult = $this->finalizeComputerTurn();
        $steps[] = $selectionResult;

        $this->startNewTurn();

        return $steps;
    }

    // --- Computer Logic (Rolling Phase) ---

    private function executeComputerRollPhase(): array {
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

            $heldIndices = $this->determineComputerHolds();
            $steps[] = ['type' => 'hold', 'heldIndices' => $heldIndices];

            if (count($heldIndices) === 5) break;
        }

        return $steps;
    }

    private function determineComputerHolds(): array {
        // EASY: Całkowicie losowe trzymanie
        if ($this->difficulty === 'easy') {
            return $this->getNoviceHolds();
        }

        // MEDIUM: Standardowa strategia + 30% szans na błąd (losowość)
        if ($this->difficulty === 'medium') {
            if (rand(1, 10) <= 3) {
                return $this->getNoviceHolds();
            }
            return $this->getStandardHeldIndices();
        }

        // HARD: Strategia Ekspercka (Pro) - bez błędów, szukanie bonusów i stritów
        $heldIndices = $this->getProHeldIndices();
        sort($heldIndices);
        return array_unique($heldIndices);
    }

    private function getNoviceHolds(): array {
        $diceValues = $this->dice;
        $counts = array_count_values($diceValues);
        arsort($counts);

        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);

        // 1. Jeśli widzi parę, trójkę itp. - trzyma to. To naturalny odruch.
        if ($maxCount >= 2) {
            return array_keys(array_filter($diceValues, fn($v) => $v == $mostFrequentVal));
        }

        // 2. Jeśli nie ma par, trzyma Szóstki (bo dają dużo punktów).
        // Nowicjusz rzadko poluje na strita, woli wysokie cyfry.
        $sixes = array_keys(array_filter($diceValues, fn($v) => $v == 6));
        if (!empty($sixes)) {
            return $sixes;
        }

        // 3. Jeśli nic nie pasuje - rzuca wszystkim (nie trzyma nic).
        return [];
    }

    // --- Strategy: Medium (Standard/Greedy) ---

    private function getStandardHeldIndices(): array {
        $diceValues = $this->dice;
        $counts = array_count_values($diceValues);
        arsort($counts);
        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);

        // 1. Yahtzee
        if ($maxCount === 5) return [0, 1, 2, 3, 4];

        // 2. 4-of-a-kind
        if ($maxCount >= 4) {
            return array_keys(array_filter($diceValues, fn($v) => $v == $mostFrequentVal));
        }

        // 3. Proste szukanie strita (tylko w oparciu o szablony)
        $straightIndices = $this->checkStandardStraightStrategy();
        if (!empty($straightIndices)) return $straightIndices;

        // 4. Trzymanie par/trójek
        if ($maxCount >= 2 || ($maxCount == 1 && $mostFrequentVal >= 4)) {
            return array_keys(array_filter($diceValues, fn($v) => $v == $mostFrequentVal));
        }

        // 5. Trzymanie najwyższej kości
        $maxVal = max($diceValues);
        return array_keys(array_filter($diceValues, fn($v) => $v == $maxVal));
    }

    private function checkStandardStraightStrategy(): array {
        $needsLg = $this->computerScorecard['score-large-straight'] === null;
        $needsSm = $this->computerScorecard['score-small-straight'] === null;

        if (!$needsLg && !$needsSm) return [];

        $uniqueDice = array_unique($this->dice);
        $straightPatterns = ['1234', '2345', '3456'];

        foreach ($straightPatterns as $pattern) {
            $matchingValues = array_intersect(str_split($pattern), $uniqueDice);
            $matches = count($matchingValues);

            if (($matches >= 4 && $needsLg) || ($matches >= 3 && $needsSm)) {
                $indicesToHold = [];
                foreach ($this->dice as $idx => $val) {
                    if (in_array($val, $matchingValues) && !in_array($idx, $indicesToHold)) {
                        $indicesToHold[] = $idx;
                    }
                }
                if (count($indicesToHold) >= 3) return $indicesToHold;
            }
        }
        return [];
    }

    // --- Strategy: Hard (Pro/Smart) ---

    private function getProHeldIndices(): array {
        $diceValues = $this->dice;
        $counts = array_count_values($diceValues);
        arsort($counts);

        $mostFrequentVal = array_key_first($counts);
        $maxCount = reset($counts);
        $uniqueDice = array_unique($diceValues);
        sort($uniqueDice);

        // 1. ZAWSZE trzymaj Yahtzee
        if ($maxCount === 5) return [0, 1, 2, 3, 4];

        // 2. Trzymaj 4 takie same
        if ($maxCount >= 4) {
            return array_keys(array_filter($diceValues, fn($v) => $v == $mostFrequentVal));
        }

        // 3. Inteligentne szukanie Strita (wykrywanie sekwencji)
        $needsLg = $this->computerScorecard['score-large-straight'] === null;
        $needsSm = $this->computerScorecard['score-small-straight'] === null;

        if ($needsLg || $needsSm) {
            $longestRun = [];
            $currentRun = [$uniqueDice[0] ?? 0];

            for ($i = 0; $i < count($uniqueDice) - 1; $i++) {
                if ($uniqueDice[$i+1] == $uniqueDice[$i] + 1) {
                    $currentRun[] = $uniqueDice[$i+1];
                } else {
                    if (count($currentRun) > count($longestRun)) $longestRun = $currentRun;
                    $currentRun = [$uniqueDice[$i+1]];
                }
            }
            if (count($currentRun) > count($longestRun)) $longestRun = $currentRun;

            // Jeśli mamy prawie Large Straight (4 sekwencyjne), olej parę, idź w strita
            if (count($longestRun) >= 4 && $needsLg) {
                return $this->getIndicesForValues($longestRun);
            }

            // Jeśli mamy 3 sekwencyjne i potrzebujemy Small, a nie mamy trójki
            if (count($longestRun) >= 3 && $needsSm && $maxCount < 3) {
                return $this->getIndicesForValues($longestRun);
            }
        }

        // 4. Polowanie na Full House (tylko jeśli potrzebny)
        if ($this->computerScorecard['score-full-house'] === null) {
            // Mamy dwie pary? Trzymaj obie.
            if ($maxCount === 2 && count($counts) === 3) { // Układ 2-2-1
                $pairs = array_keys(array_filter($counts, fn($c) => $c === 2));
                return array_keys(array_filter($diceValues, fn($v) => in_array($v, $pairs)));
            }
        }

        // 5. Standard: Budowanie Upper Section (trójek/czwórek)
        return array_keys(array_filter($diceValues, fn($v) => $v == $mostFrequentVal));
    }

    private function getIndicesForValues(array $valuesToKeep): array {
        $indices = [];
        $tempDice = $this->dice;
        foreach ($valuesToKeep as $val) {
            $key = array_search($val, $tempDice);
            if ($key !== false) {
                $indices[] = $key;
                unset($tempDice[$key]);
            }
        }
        return $indices;
    }

    // --- Computer Logic (Selection Phase) ---

    private function finalizeComputerTurn(): array {
        $this->calculatePossibleScores();

        $bestCategory = $this->decideComputerCategory();
        $points = 0;

        if ($bestCategory) {
            $points = $this->possibleScores[$bestCategory] ?? 0;
            // Obsługa "skreślania" (gdy wynik jest null w possibleScores - choć tu zazwyczaj jest 0)
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

    private function decideComputerCategory(): ?string {
        if ($this->difficulty === 'easy') {
            return $this->findNoviceCategory();
        }

        if ($this->difficulty === 'medium') {
            // Medium jest "zachłanny" - bierze najwięcej punktów
            $cat = $this->findGreedyCategory();
            return $cat ?: $this->findStandardFallbackCategory();
        }

        // HARD
        return $this->findProCategory();
    }

    // --- Selection: Easy ---
    private function findFirstAvailableCategory(): ?string {
        foreach ($this->possibleScores as $cat => $points) {
            if (($this->computerScorecard[$cat] ?? null) === null) return $cat;
        }
        return null;
    }
    private function findNoviceCategory(): ?string {
        // Nowicjusz szuka po prostu najwyższego wyniku, jaki może wpisać.
        // Nie kalkuluje "opłacalności" ani bonusów.

        $bestCategory = null;
        $maxPoints = -1;

        foreach ($this->possibleScores as $cat => $points) {
            // Pomiń zajęte
            if (($this->computerScorecard[$cat] ?? null) !== null) continue;

            // Nowicjusz weźmie cokolwiek, co daje punkty (nawet małe)
            if ($points > $maxPoints) {
                $maxPoints = $points;
                $bestCategory = $cat;
            }
        }

        // Jeśli znalazł coś za punkty (>0), bierze to.
        if ($maxPoints > 0) {
            return $bestCategory;
        }

        // Jeśli same zera (Bust), wpisuje 0 w pierwszą wolną kategorię (nie myśli co skreślać)
        return $this->findFirstAvailableCategory();
    }

    // --- Selection: Medium (Greedy) ---
    private function findGreedyCategory(): ?string {
        // Priorytet Yahtzee
        if (($this->possibleScores['score-yahtzee'] ?? 0) == 50 && $this->computerScorecard['score-yahtzee'] === null) {
            return 'score-yahtzee';
        }

        $bestCategory = null;
        $maxPoints = -1;

        foreach ($this->possibleScores as $cat => $points) {
            if (($this->computerScorecard[$cat] ?? null) !== null) continue;

            // Lekka heurystyka dla Chance
            if ($cat === 'score-chance' && $points < 20) continue;

            if ($points >= $maxPoints) {
                $maxPoints = $points;
                $bestCategory = $cat;
            }
        }
        return $bestCategory;
    }

    private function findStandardFallbackCategory(): ?string {
        $scratchOrder = ['score-aces', 'score-twos', 'score-yahtzee', 'score-four-of-a-kind', 'score-large-straight'];
        foreach ($scratchOrder as $cat) {
            if (($this->computerScorecard[$cat] ?? null) === null) return $cat;
        }
        return $this->findFirstAvailableCategory();
    }

    // --- Selection: Hard (Pro) ---
    private function findProCategory(): ?string {
        // 1. MUST HAVE: Yahtzee
        if (($this->possibleScores['score-yahtzee'] ?? 0) === 50 && $this->computerScorecard['score-yahtzee'] === null) {
            return 'score-yahtzee';
        }

        // 2. Analiza Upper Section (celujemy w Bonus +35)
        // Sprawdzamy od 6 w dół
        foreach ([6, 5, 4, 3, 2, 1] as $dieVal) {
            $catKey = $this->getCategoryKeyForDie($dieVal);
            if ($this->computerScorecard[$catKey] !== null) continue;

            $points = $this->possibleScores[$catKey];
            $count = ($points > 0) ? ($points / $dieVal) : 0;

            // Mamy 4 lub więcej -> Bierzemy (nadwyżka do bonusu)
            if ($count >= 4) return $catKey;

            // Mamy 3 -> Dobry wynik
            if ($count == 3) {
                // Wyjątek: Jeśli mamy Full House a to są Asy/Dwójki, lepiej wziąć Full House (25pkt vs 3/6pkt)
                if ($dieVal <= 2 && ($this->possibleScores['score-full-house'] ?? 0) === 25 && $this->computerScorecard['score-full-house'] === null) {
                    return 'score-full-house';
                }
                return $catKey;
            }
        }

        // 3. Lower Section - High Value
        if (($this->possibleScores['score-large-straight'] ?? 0) === 40 && $this->computerScorecard['score-large-straight'] === null) {
            return 'score-large-straight';
        }
        if (($this->possibleScores['score-small-straight'] ?? 0) === 30 && $this->computerScorecard['score-small-straight'] === null) {
            return 'score-small-straight';
        }
        if (($this->possibleScores['score-full-house'] ?? 0) === 25 && $this->computerScorecard['score-full-house'] === null) {
            return 'score-full-house';
        }

        // 4. 4-of-a-kind / 3-of-a-kind (tylko jeśli suma wysoka)
        $sum = array_sum($this->dice);
        if ($this->computerScorecard['score-four-of-a-kind'] === null && ($this->possibleScores['score-four-of-a-kind'] ?? 0) > 0) {
            if ($sum >= 20) return 'score-four-of-a-kind';
        }
        if ($this->computerScorecard['score-three-of-a-kind'] === null && ($this->possibleScores['score-three-of-a-kind'] ?? 0) > 0) {
            if ($sum >= 24) return 'score-three-of-a-kind';
        }

        // 5. Chance (tylko jeśli suma wysoka)
        if ($this->computerScorecard['score-chance'] === null && $sum >= 22) {
            return 'score-chance';
        }

        // 6. Ratowanie Bonusu (nawet 2 sztuki w Upper Section)
        foreach ([6, 5, 4] as $dieVal) {
            $catKey = $this->getCategoryKeyForDie($dieVal);
            if ($this->computerScorecard[$catKey] === null) {
                if (($this->possibleScores[$catKey] ?? 0) >= ($dieVal * 2)) return $catKey;
            }
        }

        // 7. Inteligentne Skreślanie (Scratching)
        return $this->findSmartScratchCategory();
    }

    private function findSmartScratchCategory(): string {
        // Skreślaj to co najmniej boli
        $priorities = [
            'score-aces',           // Mała strata
            'score-twos',           // Mała strata
            'score-yahtzee',        // Trudne do trafienia
            'score-four-of-a-kind', // Trudne
            'score-large-straight'  // Trudne
        ];

        foreach ($priorities as $cat) {
            if (($this->computerScorecard[$cat] ?? null) === null) return $cat;
        }

        // Cokolwiek wolnego
        return $this->findFirstAvailableCategory();
    }

    private function getCategoryKeyForDie(int $val): string {
        return match($val) {
            1 => 'score-aces', 2 => 'score-twos', 3 => 'score-threes',
            4 => 'score-fours', 5 => 'score-fives', 6 => 'score-sixes',
        };
    }

    // --- Scoring Logic ---

    private function calculatePossibleScores(): void {
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

        $this->calculateStraights();
    }

    private function calculateStraights(): void {
        $uniqueDice = array_unique($this->dice);
        sort($uniqueDice);
        $diceStr = implode('', $uniqueDice);

        $smallStraights = ['1234', '2345', '3456'];
        $largeStraights = ['12345', '23456'];

        $isSmall = false;
        foreach($smallStraights as $s) if (str_contains($diceStr, $s)) $isSmall = true;

        $isLarge = false;
        foreach($largeStraights as $l) if (str_contains($diceStr, $l)) $isLarge = true;

        $this->possibleScores['score-small-straight'] = $isSmall ? 30 : 0;
        $this->possibleScores['score-large-straight'] = $isLarge ? 40 : 0;
    }

    private function calculateScorecardTotals(array $targetScorecard): array {
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

    private function isGameOver(): bool {
        $pCount = count(array_filter($this->scorecard, fn($v) => $v !== null));
        $cCount = count(array_filter($this->computerScorecard, fn($v) => $v !== null));
        return $pCount === 13 && $cCount === 13;
    }
}