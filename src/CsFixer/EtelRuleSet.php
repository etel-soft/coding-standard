<?php

declare(strict_types=1);

namespace Etel\CodingStandard\CsFixer;

use Override;
use PhpCsFixer\RuleSet\RuleSetDefinitionInterface;

/**
 * The `@Etel/standard` rule set: the whole php-cs-fixer configuration of the etel projects in one place.
 *
 * A project enables it with `'@Etel/standard' => true` (see {@see EtelConfig}) and lists only what it does differently,
 * so a change to the standard is a version bump of this package rather than an edit in every repository.
 * It needs {@see NoTrailingCommaOutsideArraysFixer} registered, which {@see EtelConfig} does.
 */
final readonly class EtelRuleSet implements RuleSetDefinitionInterface
{
    public const string NAME = '@Etel/standard';

    #[Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Coding standard of the etel projects: PSR-12, @PhpCsFixer and the PHP migrations, with house rules.';
    }

    #[Override]
    public function isRisky(): bool
    {
        return true;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            '@PSR12' => true,
            '@PHP8x0Migration' => true,
            '@PHP8x1Migration' => true,
            '@PHP8x2Migration' => true,
            '@PHP8x3Migration' => true,
            '@PHP8x4Migration' => true,
            '@PHP8x5Migration' => true,
            '@PhpCsFixer' => true,
            'array_syntax' => ['syntax' => 'short'],
            'blank_line_before_statement' => [
                'statements' => [
                    'break', 'case', 'continue', 'declare', 'default', 'do', 'for',
                    'foreach', 'if', 'return', 'switch', 'throw', 'try', 'while', 'yield',
                ],
            ],
            'class_attributes_separation' => [
                'elements' => ['method' => 'one', 'property' => 'one', 'trait_import' => 'none'],
            ],
            'class_definition' => ['single_line' => false],
            'concat_space' => ['spacing' => 'one'],
            'declare_strict_types' => true,
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => true,
                'import_functions' => true,
            ],
            'is_null' => true,
            'method_argument_space' => ['on_multiline' => 'ignore'],
            // a get-visibility of public is implied by private(set) and stays unwritten
            'modifier_keywords' => ['elements' => ['const', 'method']],
            // disabled: it rewrote byte-exact strlen/substr on binary data into character-based mb_* equivalents
            'mb_str_functions' => false,
            'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
            'native_function_invocation' => ['include' => ['@all']],
            'no_extra_blank_lines' => [
                'tokens' => [
                    'case', 'continue', 'curly_brace_block', 'default', 'extra',
                    'parenthesis_brace_block', 'return', 'square_brace_block',
                    'switch', 'throw', 'use',
                ],
            ],
            'non_printable_character' => ['use_escape_sequences_in_strings' => true],
            'no_superfluous_phpdoc_tags' => false,
            'no_useless_else' => true,
            'no_useless_return' => true,
            'ordered_class_elements' => true,
            'ordered_imports' => ['imports_order' => ['class', 'const', 'function'], 'sort_algorithm' => 'alpha'],
            'phpdoc_line_span' => [
                'class' => 'multi',
                'function' => 'multi',
                'method' => 'multi',
                'const' => null,
                'property' => null,
                'case' => null,
            ],
            'phpdoc_order' => true,
            'phpdoc_separation' => ['skip_unlisted_annotations' => true],
            'phpdoc_to_comment' => ['ignored_tags' => ['todo', 'var'], 'allow_before_return_statement' => true],
            'phpdoc_trim_consecutive_blank_line_separation' => true,
            'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
            'php_unit_test_class_requires_covers' => false,
            'psr_autoloading' => true,
            'single_line_comment_style' => ['comment_types' => ['hash']],
            'single_line_throw' => false,
            'strict_comparison' => true,
            // a trailing comma after the last element of a multi-line array only: the built-in rule adds it there,
            // and the custom one takes it away from arguments, parameters, closure `use` lists and `match` arms
            'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arrays']],
            NoTrailingCommaOutsideArraysFixer::NAME => true,
            'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        ];
    }
}
