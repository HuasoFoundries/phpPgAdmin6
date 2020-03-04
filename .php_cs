<?php

declare(strict_types=1);

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
 */

use Ergebnis\PhpCsFixer\Config;

$composerinfo = \json_decode(\file_get_contents('composer.json'));

$tags = [
    'master' => \trim(\shell_exec('git describe --tags master')),

    'develop' => \trim(\shell_exec('git describe --tags develop')),
];

$current_branch = \trim(\shell_exec('git rev-parse --abbrev-ref HEAD'));

$version = $tags[$current_branch] ?? $tags['develop'];

$composer_tags = $composerinfo->extra->current_tags;
\var_dump($composerinfo->extra->current_tags);

if (
    \array_key_exists(\trim($current_branch), $tags) &&
    ($tags['master'] !== $composer_tags->master
    || $tags['develop'] !== $composer_tags->develop)
) {
    $composerinfo->extra->current_tags->master = $tags['master'];
    $composerinfo->extra->current_tags->develop = $tags['develop'];
    $composerinfo->extra->version = $version;
    \file_put_contents('composer.json', \json_encode($composerinfo, \JSON_PRETTY_PRINT));
}

$header = "PHPPgAdmin {$version}";

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php71($header), [
    'declare_strict_types' => false,
    //'header_comment' => ['commentType' => 'PHPDoc', 'header' => $header],
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
    ->exclude([
        '.build',
        '.configs',
        '__pycache__',
        'assets',
        'docs',
        'node_modules',
        'temp',
        'tests',
        'vendor',
        '.github',
    ])
    ->name('.php_cs');

$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/php_cs.cache');

return $config;
