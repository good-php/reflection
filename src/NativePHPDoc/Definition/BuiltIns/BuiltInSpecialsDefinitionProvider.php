<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns;

use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

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
				new Collection(),
				new Collection([
					new NamedType('float'),
				])
			)),
			'float' => lazy(fn () => new SpecialTypeDefinition(
				'float',
			)),
			'bool' => lazy(fn () => new SpecialTypeDefinition(
				'bool',
			)),
			'iterable' => lazy(fn () => new SpecialTypeDefinition(
				'iterable',
				new Collection([
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
				])
			)),
			'array' => lazy(fn () => new SpecialTypeDefinition(
				'array',
				new Collection([
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
				]),
				new Collection([
					new NamedType('iterable', new Collection([
						new TemplateType(
							name: 'TKey',
						),
						new TemplateType(
							name: 'TValue',
						),
					])),
				])
			)),
			'callable' => lazy(fn () => new SpecialTypeDefinition(
				'callable',
				new Collection([
					new TypeParameterDefinition(
						name: 'TReturn',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TParameter',
						variadic: true,
						upperBound: null,
						variance: TemplateTypeVariance::CONTRAVARIANT
					),
				])
			)),
		];
	}

	public function forType(string $type): ?SpecialTypeDefinition
	{
		return ($this->typeDefinitions[$type] ?? null)?->value();
	}
}
