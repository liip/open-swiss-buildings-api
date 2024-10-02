<?php

declare(strict_types=1);

namespace App\Domain\Resolving\Model\Failure;

use App\Domain\Resolving\Exception\RetryableResolvingErrorException;

/**
 * @phpstan-type ResolverJobFailureAsArray array{type: string, details: string, retryable?: bool}
 */
final readonly class ResolverJobFailure implements \Stringable, \JsonSerializable
{
    private function __construct(
        public ResolverJobFailureEnum $type,
        public string $details,
        public bool $retryable = false,
    ) {}

    public static function fromException(ResolverJobFailureEnum $type, \Throwable $exception): self
    {
        return new self(
            $type,
            $exception->getMessage(),
            $exception instanceof RetryableResolvingErrorException,
        );
    }

    /**
     * @param ResolverJobFailureAsArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(ResolverJobFailureEnum::from($data['type']), $data['details'], $data['retryable'] ?? false);
    }

    public function __toString(): string
    {
        $retryable = $this->retryable ? '[Retryable]' : '';

        return "{$this->type->value} => {$this->details} {$retryable}";
    }

    /**
     * @return ResolverJobFailureAsArray
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'details' => $this->details,
            'retryable' => $this->retryable,
        ];
    }
}
