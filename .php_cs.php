<?php

declare(strict_types=1);

/**
 * PHPPgAdmin 6.1.0
 */

use Ergebnis\PhpCsFixer\Config;

$composerinfo = \json_decode(\file_get_contents('composer.json'));

$version = $composerinfo->extra->version;

$header = "PHPPgAdmin {$version}";

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php71($header), [
    'declare_strict_types' => false,
    'escape_implicit_backslashes' => false,
    'final_class' => false,
    'final_internal_class' => false,
    'final_public_method_for_abstract_class' => false,
    'final_static_access' => false,
    'global_namespace_import' => false,
    'fully_qualified_strict_types' => false,
    'visibility_required' => [
        'elements' => [
            'method',
            'property',
        ],
    ],
]);

$config->getFinder()
    ->ignoreDotFiles(false)
    ->in(__DIR__)
    ->notName('rector.php')
    ->exclude([
        '.build',
        '.configs',
        '__pycache__',
        'assets',
        'docs',
        'node_modules',
        'temp',
        'rector.php',
        'src/router.php',
        'vendor',
        '.github',
    ])
    ->name('.php_cs.php');

$config->setCacheFile(__DIR__ . '/.build/phpcs/csfixer.cache');

return $config;
