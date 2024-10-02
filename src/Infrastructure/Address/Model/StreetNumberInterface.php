<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Model;

interface StreetNumberInterface extends \Stringable
{
    public function equalsTo(self $other): bool;
}
