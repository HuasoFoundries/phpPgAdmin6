<?php

declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 */

use Ergebnis\PhpCsFixer\Config;

$composerinfo = \json_decode(\file_get_contents('composer.json'));


$tags = [
    'master' => trim(shell_exec('git describe --tags master')),

    'develop' => trim(shell_exec('git describe --tags develop')),
];

$current_branch = \trim(\shell_exec('git rev-parse --abbrev-ref HEAD'));

$version = $tags[$current_branch] ?? $tags['develop'];


$composer_tags = $composerinfo->extra->current_tags;
var_dump($composerinfo->extra->current_tags);
if (
    \array_key_exists( trim($current_branch), $tags) &&
    ($tags['master'] !== $composer_tags->master
    || $tags['develop'] !== $composer_tags->develop)
) {
    $composerinfo->extra->current_tags->master = $tags['master'];
    $composerinfo->extra->current_tags->develop = $tags['develop'];
    $composerinfo->extra->version = $version;
    \file_put_contents('composer.json', \json_encode($composerinfo));
}

$header = "PHPPgAdmin {$version}";
;

$rules = [
    'no_php4_constructor' => true,
    '@PHP56Migration' => true,
    '@PHPUnit60Migration:risky' => true,
    '@Symfony' => true,
    '@Symfony:risky' => false,
    '@PSR1' => true,
    '@PSR2' => true,
    'align_multiline_comment' => true,
    'array_syntax' => ['syntax' => 'short'],
    'blank_line_before_statement' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'compact_nullable_typehint' => true,
    'escape_implicit_backslashes' => true,
    'explicit_indirect_variable' => true,
    'no_whitespace_in_blank_line' => true,
    'no_unused_imports' => true,
    'elseif' => true,
    'explicit_string_variable' => true,
    'final_internal_class' => true,
    'modernize_types_casting' => true,
    'header_comment' => ['commentType' => 'PHPDoc', 'header' => $header],
    'heredoc_to_nowdoc' => true,
    'phpdoc_no_package' => false,
    'list_syntax' => ['syntax' => 'long'],
    'method_chaining_indentation' => true,
    'method_argument_space' => ['ensure_fully_multiline' => true],
    'multiline_comment_opening_closing' => true,
    'no_extra_blank_lines' => ['tokens' => ['break', 'case', 'continue', 'curly_brace_block', 'default', 'extra', 'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw', 'use', 'useTrait', 'use_trait']],
    'no_null_property_initialization' => true,
    'no_short_echo_tag' => true,
    'no_superfluous_elseif' => true,
    'no_unneeded_curly_braces' => true,
    'no_unneeded_final_method' => true,
    'no_unreachable_default_argument_value' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_class_elements' => false,
    'ordered_imports' => true,
    'php_unit_strict' => true,
    'php_unit_test_annotation' => true,
    'php_unit_test_class_requires_covers' => true,
    'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_align' => true,
    'phpdoc_order' => true,
    'phpdoc_separation' => true,
    'phpdoc_scalar' => true,
    'phpdoc_trim' => true,
    'phpdoc_types_order' => true,
    'semicolon_after_instruction' => true,
    'single_line_comment_style' => false,
    'strict_comparison' => false,
    'strict_param' => true,
    'single_quote' => true,
    'yoda_style' => false,
    'binary_operator_spaces' => [
        'align_double_arrow' => true,
        'align_equals' => true,
    ],
];

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php71($header),[
     'declare_strict_types' => false,
      'header_comment' => ['commentType' => 'PHPDoc', 'header' => $header],
      'escape_implicit_backslashes' => false,
      'final_class'=>false,
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
    ->in(__DIR__.'/src/decorators')
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

/*$config = PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src/classes')
            ->in(__DIR__.'/src/controllers')
            ->in(__DIR__.'/src/database')
            ->in(__DIR__.'/src/decorators')
            ->in(__DIR__.'/src/help')
            ->in(__DIR__.'/src/translations')
            ->in(__DIR__.'/src/xhtml')
            ->in(__DIR__.'/src/traits')
            ->in(__DIR__.'/src/database/databasetraits')
            ->in(__DIR__.'/tests')
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
    ->name('.php_cs')

    );*/
$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/php_cs.cache');

// special handling of fabbot.io service if it's using too old PHP CS Fixer version
/*
try {
    PhpCsFixer\FixerFactory::create()
        ->registerBuiltInFixers()
        ->registerCustomFixers($config->getCustomFixers())
        ->useRuleSet(new PhpCsFixer\RuleSet($config->getRules()));
} catch (PhpCsFixer\ConfigurationException\InvalidConfigurationException $e) {
    $config->setRules([]);
} catch (UnexpectedValueException $e) {
    $config->setRules([]);
} catch (InvalidArgumentException $e) {
    $config->setRules([]);
}
*/
return $config;
