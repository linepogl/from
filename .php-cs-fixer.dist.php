<?php

const CACHE = 'var/cache/.phpcsfix/.php-cs-fixer.cache';
const PATHS = [
    __DIR__ . '/src',
    __DIR__ . '/tst',
];
const RULES = [
    '@PSR12'                                     => true,
    'array_indentation'                          => true,
    'global_namespace_import'                    => true,
    'group_import'                               => false,
    'heredoc_indentation'                        => false,
    'lambda_not_used_import'                     => true,
    'no_trailing_comma_in_singleline'            => true,
    'no_unused_imports'                          => true,
    'ordered_imports'                            => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    'php_unit_method_casing'                     => ['case' => 'snake_case'],
    'single_import_per_statement'                => ['group_to_single_imports' => true],
    'standardize_not_equals'                     => true,
    'trailing_comma_in_multiline'                => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
    'array_push'                                 => true,
    'combine_nested_dirname'                     => true,
    'dir_constant'                               => true,
    'modernize_strpos'                           => true,
    'native_function_invocation'                 => ['include' => [], 'strict'  => true],
    'non_printable_character'                    => true,
    'php_unit_test_case_static_method_calls'     => ['call_type' => 'this'],
    'static_lambda'                              => false,
    'declare_strict_types'                       => true,
    'no_useless_else'                            => true,
    'no_useless_return'                          => true,
];

return (new PhpCsFixer\Config())
    ->setCacheFile(CACHE)
    ->setRiskyAllowed(true)
    ->setRules(RULES)
    ->setFinder((new PhpCsFixer\Finder())->in(PATHS));
