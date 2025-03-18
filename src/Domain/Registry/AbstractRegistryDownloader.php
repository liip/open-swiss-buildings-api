<?php

declare(strict_types=1);

namespace App\Domain\Registry;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract readonly class AbstractRegistryDownloader
{
    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private Filesystem $filesystem,
    ) {}

    abstract protected function getRegistryURL(): string;

    /**
     * @throws ClientExceptionInterface
     */
    final public function download(string $target, bool $force = false): bool
    {
        $eTagFile = "{$target}.etag";

        $registryURL = $this->getRegistryURL();
        $request = $this->requestFactory->createRequest('GET', $registryURL);
        if (!$force && $this->filesystem->exists($eTagFile)) {
            $eTag = file_get_contents($eTagFile);
            if (false !== $eTag && '' !== $eTag) {
                $request = $request->withHeader('If-None-Match', $eTag);
            }
        }

        $response = $this->http->sendRequest($request);

        if (304 === $response->getStatusCode()) {
            // Cached file is still current
            return false;
        }
        if (200 !== $response->getStatusCode()) {
            throw new \UnexpectedValueException("Request to {$registryURL} failed with status code {$response->getStatusCode()}");
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if ('application/zip' !== $contentType) {
            throw new \UnexpectedValueException("Invalid content type returned, expected application/zip, got: {$contentType}!");
        }

        $stream = $response->getBody()->detach();
        if (null === $stream) {
            throw new \UnexpectedValueException('No stream found in response!');
        }

        $targetStream = fopen($target, 'w');
        if (false === $targetStream) {
            throw new \UnexpectedValueException("Could not open target file {$target}");
        }

        stream_copy_to_stream($stream, $targetStream);
        $this->filesystem->dumpFile($eTagFile, $response->getHeaderLine('ETag'));

        return true;
    }
}
