<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\Symfony\CodeQuality\Rector\Class_\EventListenerToEventSubscriberRector;
use Rector\Symfony\Set\SymfonySetList;

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
        DisallowedShortTernaryRuleFixerRector::class,
        EventListenerToEventSubscriberRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
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
        strictBooleans: true,
    )
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true)
    ->withComposerBased(doctrine: true)
    ->withSets([
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_ORM_214,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        ReadOnlyClassRector::class,
    ])
;
