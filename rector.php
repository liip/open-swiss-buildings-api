<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\CodeQuality\Rector\Class_\EventListenerToEventSubscriberRector;
use Rector\Symfony\Symfony73\Rector\Class_\InvokableCommandInputAttributeRector;

$symfonyContainer = __DIR__ . '/var/cache/test/App_KernelTestDebugContainer.xml';
if (file_exists(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')) {
    $symfonyContainer = __DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml';
}

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        '**/config/bundles.php',
        EventListenerToEventSubscriberRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        NewlineAfterStatementRector::class,
    ])
    ->withCache(
        cacheDirectory: 'var/cache/ci/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withSymfonyContainerXml($symfonyContainer)
    ->withImportNames(importShortClasses: false)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        instanceOf: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
    )
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true)
    ->withComposerBased(doctrine: true, symfony: true)
    ->withSets([
        PHPUnitSetList::PHPUNIT_110,
    ])
    ->withCodingStyleLevel(8)
    ->withRules([
        ReadOnlyPropertyRector::class,
        ReadOnlyClassRector::class,
    ])
    ->withSkip([
        // disable until symfony 7.4, see https://github.com/liip/open-swiss-buildings-api/pull/234
        InvokableCommandInputAttributeRector::class,
    ])
;
