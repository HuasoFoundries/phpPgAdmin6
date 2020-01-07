<?php

$composerinfo          = json_decode(file_get_contents('composer.json'));
$version = $composerinfo->version;

$header = "PHPPgAdmin v$version";

$rules=[
    '@PHP56Migration' => true,
    '@PHPUnit60Migration:risky' => true,
    '@PSR1'=>true,
    '@PSR2'=>true,
    '@Symfony' => true,
    '@Symfony:risky' => false,
    'align_multiline_comment' => true,
    'align_double_arrow' => true,
    'align_equals' => true,
    'array_syntax' => ['syntax' => 'short'],
    'blank_line_before_statement' => true,
    'blank_line_after_namespace'=>false,
    'blank_line_after_opening_tag'=>false,
    'blank_line_before_break'=>false,
    'blank_line_before_continue'=>false,
    'blank_line_before_declare'=>false,
    'blank_line_before_return'=>false,
    'blank_line_before_throw'=>false,
    'blank_line_before_try'=>false,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'compact_nullable_typehint' => true,
    'elseif'=>true,
    'escape_implicit_backslashes' => true,
    'explicit_indirect_variable' => true,
    'explicit_string_variable' => true,
    'final_internal_class' => true,
    'header_comment' => ['commentType'=>'PHPDoc','header' => $header],
    'heredoc_to_nowdoc' => true,
    'list_syntax' => ['syntax' => 'long'],
    'method_argument_space' => ['ensure_fully_multiline' => true],
    'method_chaining_indentation' => true,
    'modernize_types_casting'=>true,
    'multiline_comment_opening_closing' => true,
    'no_null_property_initialization' => true,
    'no_php4_constructor'=>true,
    'no_short_echo_tag' => true,
    'no_superfluous_elseif' => true,
    'no_unneeded_curly_braces' => true,
    'no_unneeded_final_method' => true,
    'no_unreachable_default_argument_value' => true,
    'no_unused_imports'=>true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_whitespace_in_blank_line'=> true,
    'ordered_class_elements' => false,
    'ordered_imports' => true,
    'php_unit_strict' => true,
    'php_unit_test_annotation' => true,
    'php_unit_test_class_requires_covers' => true,
    'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_align'=>true,
    'phpdoc_no_package' => false,
    'phpdoc_order' => true,
    'phpdoc_scalar'=>true,
    'phpdoc_separation'=>true,
    'phpdoc_trim'=>true,
    'phpdoc_types_order' => true,
    'semicolon_after_instruction' => true,
    'single_line_comment_style' => false,
    'single_quote'=>true,
    'strict_comparison' => false,
    'strict_param' => true,
    'yoda_style' => false,
    'no_extra_blank_lines' => [
        'tokens' => 
            [
                'if',
                'break', 
                'case', 
                'continue', 
                'curly_brace_block',
                'default', 'extra', 
                'parenthesis_brace_block', 
                'return', 
                'square_brace_block', 
                'switch', 
                'throw', 
                'use', 
                'useTrait', 
                'use_trait'
            ]
    ],
     
        'binary_operator_spaces' => [
            'align_double_arrow' => true,
            'align_equals' => true
        ]

];
        
$config = PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src/classes')
            ->in(__DIR__.'/src/middleware')
            ->in(__DIR__.'/src/controllers')
            ->in(__DIR__.'/src/database')
            ->in(__DIR__.'/src/decorators')
            ->in(__DIR__.'/src/help')
            ->in(__DIR__.'/src/translations')
            ->in(__DIR__.'/src/xhtml')
            ->in(__DIR__.'/src/traits')
            ->in(__DIR__.'/src/database/databasetraits')
            ->in(__DIR__.'/tests')
            
    );

// special handling of fabbot.io service if it's using too old PHP CS Fixer version
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
        
return $config;
