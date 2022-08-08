<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
	->in('src')
	->in('tests')
	->name('*.php')
	->notName('_*.php')
	->ignoreVCS(true);

return (new Config())
	->setFinder($finder)
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setRules([
		'@PhpCsFixer'                              => true,
		'@Symfony'                                 => true,
		'@Symfony:risky'                           => true,
		'@PSR12'                                   => true,
		'@PSR12:risky'                             => true,
		'assign_null_coalescing_to_coalesce_equal' => true,
		'modernize_strpos'                         => true,
		'empty_loop_condition'                     => true,
		'declare_parentheses'                      => true,
		'array_syntax'                             => ['syntax' => 'short'],
		'binary_operator_spaces'                   => [
			'default'   => 'single_space',
			'operators' => [
				'=>' => 'align_single_space_minimal',
			],
		],
		'blank_line_before_statement' => [
			'statements' => [
				'break', 'continue', 'declare', 'default', 'do', 'while', 'for',
				'foreach', 'goto', 'if', 'return', 'switch', 'throw', 'try', 'yield',
			],
		],
		'braces' => [
			'position_after_control_structures'                 => 'same',
			'allow_single_line_anonymous_class_with_empty_body' => true,
		],
		'class_attributes_separation' => [
			'elements' => ['method' => 'one', 'property' => 'one'],
		],
		'concat_space' => [
			'spacing' => 'one',
		],
		'increment_style' => [
			'style' => 'post',
		],
		'linebreak_after_opening_tag' => true,
		'native_function_invocation'  => false,
		'ordered_class_elements'      => [
			'order' => [
				'use_trait',
				'constant_public', 'constant_protected', 'constant_private',
				'property_static', 'property_public_static', 'property_protected_static', 'property_private_static',
				'property_public', 'property_protected', 'property_private',
				'construct', 'destruct', 'magic', 'phpunit',
				'method_static', 'method_public_static', 'method_protected_static', 'method_private_static',
				'method_public', 'method_protected', 'method_private',
			],
		],
		'ordered_imports'            => true,
		'phpdoc_no_empty_return'     => false,
		'phpdoc_summary'             => false,
		'ternary_to_null_coalescing' => true,
		'visibility_required'        => [
			'elements' => [
				'const', 'property', 'method',
			],
		],
		'yoda_style'                          => ['always_move_variable' => true, 'equal' => false, 'identical' => false],
		'php_unit_test_class_requires_covers' => false,
		'php_unit_internal_class'             => false,
		'protected_to_private'                => false,
		// Adds stupid semicolons on newlines
		'multiline_whitespace_before_semicolons' => false,
		// Doesn't work with "nested" chaining
		'method_chaining_indentation' => true,
		'mb_str_functions'            => true,
		'global_namespace_import'     => true,
		'list_syntax'                 => [
			'syntax' => 'short',
		],
		'phpdoc_line_span' => [
			'const'    => 'single',
			'property' => 'single',
		],
		'general_phpdoc_annotation_remove' => [
			// We don't believe so called "checked exceptions" are a good design. We're following the logic of Kotlin:
			// https://kotlinlang.org/docs/reference/exceptions.html#checked-exceptions
			'annotations' => ['throws'],
		],
		'error_suppression' => [
			// Mutes trigger_error() with E_USER_DEPRECATED, but we handle deprecations manually so this is needed.
			'mute_deprecation_error' => false,
		],
		'operator_linebreak' => [
			'only_booleans' => true,
			'position'      => 'end',
		],
		'native_constant_invocation'       => false,
		'simplified_if_return'             => true,
		'heredoc_indentation'              => true,
		'use_arrow_functions'              => true,
		'no_trailing_whitespace_in_string' => false,
		'phpdoc_no_alias_tag'              => [
			// Default except property-read and property-write replacement
			'replacements' => ['type' => 'var', 'link' => 'see'],
		],
		// Force {@inheritDoc} to @inheritDoc whenever possible.
		'phpdoc_tag_type' => [
			'tags' => [
				'inheritDoc' => 'annotation',
			],
		],
		// Rename lower case & plural form @inheritDoc
		'general_phpdoc_tag_rename' => [
			'replacements' => [
				'inheritDocs' => 'inheritDoc',
				'inheritdoc'  => 'inheritDoc',
			],
		],
	]);
