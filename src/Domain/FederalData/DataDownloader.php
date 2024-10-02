<?php

declare(strict_types=1);

namespace App\Domain\FederalData;

use App\Domain\FederalData\Contract\DataDownloaderInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class DataDownloader implements DataDownloaderInterface
{
    private const string URL = 'https://public.madd.bfs.admin.ch/ch.zip';

    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private Filesystem $filesystem,
    ) {}

    public function download(string $target, bool $force = false): bool
    {
        $eTagFile = "{$target}.etag";

        $request = $this->requestFactory->createRequest('GET', self::URL);
        if (!$force && $this->filesystem->exists($eTagFile)) {
            $eTag = file_get_contents($eTagFile);
            if (false !== $eTag) {
                $request = $request->withHeader('If-None-Match', $eTag);
            }
        }

        $response = $this->http->sendRequest($request);

        if (304 === $response->getStatusCode()) {
            // Cached file is still current
            return false;
        }
        if (200 !== $response->getStatusCode()) {
            throw new \UnexpectedValueException('Request to ' . self::URL . " failed with status code {$response->getStatusCode()}");
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
