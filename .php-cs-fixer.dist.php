<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        'templates',
        'tools',
        'var',
    ])
;

return (new Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile('var/cache/ci/php-cs-fixer.cache')
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PSR2' => true,
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'final_public_method_for_abstract_class' => true,
        'heredoc_indentation' => true,
        'list_syntax' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_property_type' => true,
        'phpdoc_to_return_type' => true,
        'phpdoc_summary' => true,
        'regular_callable_call' => true,
        'simplified_null_return' => true,
        'ternary_to_null_coalescing' => true,
        'use_arrow_functions' => true,
        'void_return' => true,
        'method_chaining_indentation' => false,
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],

        // PHPUnit
        'php_unit_dedicate_assert' => true,
        'php_unit_dedicate_assert_internal_type' => true,
        'php_unit_expectation' => true,
        'php_unit_internal_class' => false,
        'php_unit_mock' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'this',
        ],
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder)
;
