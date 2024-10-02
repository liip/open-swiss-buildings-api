<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch\Model;

enum RankingScoreRuleEnum: string
{
    case WORDS = 'words';
    case TYPO = 'typo';
    case PROXIMITY = 'proximity';
    case ATTRIBUTE = 'attribute';
    case EXACTNESS = 'exactness';
}
