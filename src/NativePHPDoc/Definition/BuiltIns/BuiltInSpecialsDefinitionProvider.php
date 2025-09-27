<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns;

use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Util\Lazy\Lazy;

use function GoodPhp\Reflection\Util\Lazy\lazy;

class BuiltInSpecialsDefinitionProvider implements DefinitionProvider
{
	/** @var array<string, Lazy<SpecialTypeDefinition>> */
	private readonly array $typeDefinitions;

	public function __construct()
	{
		$this->typeDefinitions = [
			'object' => lazy(fn () => new SpecialTypeDefinition(
				'object',
			)),
			'string' => lazy(fn () => new SpecialTypeDefinition(
				'string',
			)),
			'int' => lazy(fn () => new SpecialTypeDefinition(
				'int',
				superTypes: [
					new NamedType('float'),
				]
			)),
			'float' => lazy(fn () => new SpecialTypeDefinition(
				'float',
			)),
			'bool' => lazy(fn () => new SpecialTypeDefinition(
				'bool',
			)),
			'iterable' => lazy(fn () => new SpecialTypeDefinition(
				'iterable',
				[
					new TypeParameterDefinition(
						name: 'TKey',
						description: null,
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						description: null,
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
				]
			)),
			'array' => lazy(fn () => new SpecialTypeDefinition(
				'array',
				[
					new TypeParameterDefinition(
						name: 'TKey',
						description: null,
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						description: null,
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
				],
				[
					new NamedType('iterable', [
						new TemplateType(
							name: 'TKey',
						),
						new TemplateType(
							name: 'TValue',
						),
					]),
				]
			)),
			'callable' => lazy(fn () => new SpecialTypeDefinition(
				'callable',
				[
					new TypeParameterDefinition(
						name: 'TReturn',
						description: null,
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TParameter',
						description: null,
						variadic: true,
						upperBound: null,
						variance: TemplateTypeVariance::CONTRAVARIANT
					),
				]
			)),
		];
	}

	public function forType(string $type): ?SpecialTypeDefinition
	{
		return ($this->typeDefinitions[$type] ?? null)?->value();
	}
}
