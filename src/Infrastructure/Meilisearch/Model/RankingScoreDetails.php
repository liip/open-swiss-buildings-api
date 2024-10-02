<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Model;

/**
 * @phpstan-import-type DetailsAsArray from RankingScoreRuleDetails
 */
final readonly class RankingScoreDetails
{
    public function __construct(
        /**
         * @var list<RankingScoreRuleDetails>
         */
        public array $details,
    ) {}

    /**
     * @param array<string, DetailsAsArray> $rankingScoreDetails
     */
    public static function fromArray(array $rankingScoreDetails): self
    {
        $details = [];

        foreach ($rankingScoreDetails as $name => $scoreDetails) {
            $details[] = RankingScoreRuleDetails::fromArray($name, $scoreDetails);
        }

        return new self($details);
    }
}
