<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Model;

/**
 * Ranking score details returned by Meilisearch.
 *
 * @see https://www.meilisearch.com/docs/reference/api/search#ranking-score-details
 *
 * @phpstan-type DetailsAsArray array{
 *     order: positive-int,
 *     score?: float,
 *     matchingWords?: positive-int,
 *     maxMatchingWords?: positive-int,
 *     typoCount?: positive-int,
 *     maxTypoCount?: positive-int,
 *     attributeRankingOrderScore?: float,
 *     queryWordDistanceScore?: float,
 *     matchType?: string,
 *  }
 */
final readonly class RankingScoreRuleDetails implements \Stringable
{
    private function __construct(
        public RankingScoreRuleEnum $rule,
        /**
         * @var positive-int
         */
        public int $order,
        public ?float $score = null,
        /**
         * @var non-negative-int|null
         */
        public ?int $matchingWords = null,
        /**
         * @var non-negative-int|null
         */
        public ?int $maxMatchingWords = null,
        /**
         * @var non-negative-int|null
         */
        public ?int $typoCount = null,
        /**
         * @var non-negative-int|null
         */
        public ?int $maxTypoCount = null,
        public ?float $attributeRankingOrderScore = null,
        public ?float $queryWordDistanceScore = null,
        public ?string $matchType = null,
        public ?string $value = null,
        public ?int $distance = null,
        public ?float $similarity = null,
    ) {}

    /**
     * @param DetailsAsArray $data
     */
    public static function fromArray(string $name, array $data): self
    {
        $score = 0.0;
        if (\array_key_exists('score', $data)) {
            $score = round($data['score'], 2);
        }

        return match ($name) {
            RankingScoreRuleEnum::WORDS->value => self::forWords(
                $data['order'],
                $score,
                $data['matchingWords'] ?? 0,
                $data['maxMatchingWords'] ?? 0,
            ),
            RankingScoreRuleEnum::TYPO->value => self::forTypo(
                $data['order'],
                $score,
                $data['typoCount'] ?? 0,
                $data['maxTypoCount'] ?? 0,
            ),
            RankingScoreRuleEnum::PROXIMITY->value => self::forProximity(
                $data['order'],
                $score,
            ),
            RankingScoreRuleEnum::ATTRIBUTE->value => self::forAttribute(
                $data['order'],
                $score,
                \array_key_exists('attributeRankingOrderScore', $data) ? round($data['attributeRankingOrderScore'], 2) : 0.0,
                \array_key_exists('queryWordDistanceScore', $data) ? round($data['queryWordDistanceScore'], 2) : 0.0,
            ),
            RankingScoreRuleEnum::EXACTNESS->value => self::forExactness(
                $data['order'],
                $score,
                $data['matchType'] ?? '',
                $data['matchingWords'] ?? 0,
                $data['maxMatchingWords'] ?? 0,
            ),
            default => throw new \UnexpectedValueException("Ranking rule details for {$name} aren't implemented yet"),
        };
    }

    /**
     * @param positive-int     $order
     * @param non-negative-int $matchingWords
     * @param non-negative-int $maxMatchingWords
     */
    public static function forWords(
        int $order,
        float $score,
        int $matchingWords,
        int $maxMatchingWords,
    ): self {
        return new self(
            rule: RankingScoreRuleEnum::WORDS,
            order: $order,
            score: $score,
            matchingWords: $matchingWords,
            maxMatchingWords: $maxMatchingWords,
        );
    }

    /**
     * @param positive-int     $order
     * @param non-negative-int $typoCount
     * @param non-negative-int $maxTypoCount
     */
    public static function forTypo(
        int $order,
        float $score,
        int $typoCount,
        int $maxTypoCount,
    ): self {
        return new self(
            rule: RankingScoreRuleEnum::TYPO,
            order: $order,
            score: $score,
            typoCount: $typoCount,
            maxTypoCount: $maxTypoCount,
        );
    }

    /**
     * @param positive-int $order
     */
    public static function forProximity(
        int $order,
        float $score,
    ): self {
        return new self(
            rule: RankingScoreRuleEnum::PROXIMITY,
            order: $order,
            score: $score,
        );
    }

    /**
     * @param positive-int $order
     */
    public static function forAttribute(
        int $order,
        float $score,
        ?float $attributeRankingOrderScore = null,
        ?float $queryWordDistanceScore = null,
    ): self {
        return new self(
            rule: RankingScoreRuleEnum::ATTRIBUTE,
            order: $order,
            score: $score,
            attributeRankingOrderScore: $attributeRankingOrderScore,
            queryWordDistanceScore: $queryWordDistanceScore,
        );
    }

    /**
     * @param positive-int     $order
     * @param non-negative-int $matchingWords
     * @param non-negative-int $maxMatchingWords
     */
    public static function forExactness(
        int $order,
        float $score,
        string $matchType,
        int $matchingWords,
        int $maxMatchingWords,
    ): self {
        return new self(
            rule: RankingScoreRuleEnum::EXACTNESS,
            order: $order,
            score: $score,
            matchType: $matchType,
            matchingWords: $matchingWords,
            maxMatchingWords: $maxMatchingWords,
        );
    }

    public function __toString(): string
    {
        return match ($this->rule) {
            RankingScoreRuleEnum::WORDS => "[words] {$this->score}, matched on {$this->matchingWords}/{$this->maxMatchingWords} words",
            RankingScoreRuleEnum::TYPO => "[typo] {$this->score}, counted {$this->typoCount}/{$this->maxTypoCount} typos",
            RankingScoreRuleEnum::PROXIMITY => "[proximity] {$this->score}",
            RankingScoreRuleEnum::ATTRIBUTE => "[attribute] {$this->score}, with attribute ranking of {$this->attributeRankingOrderScore} and word distance of {$this->queryWordDistanceScore}",
            RankingScoreRuleEnum::EXACTNESS => "[exactness] {$this->score}, matched by {$this->matchType}" . ('noExactMatch' === $this->matchType ? " on {$this->matchingWords}/{$this->maxMatchingWords} words" : ''),
        };
    }
}
