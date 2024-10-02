<?php

declare(strict_types=1);

namespace App\Domain\AddressSearch\Exception;

use Symfony\Component\Uid\Uuid;

final class BuildingAddressNotFoundException extends \RuntimeException
{
    public function __construct(Uuid $id)
    {
        parent::__construct("Building address with ID {$id} could not be found, did you forget to run the import first?");
    }
}
