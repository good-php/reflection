<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns;

use ArrayAccess;
use Closure;
use Countable;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use GoodPhp\Reflection\Type\Combinatorial\ExpandedType;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Util\Lazy\Lazy;
use Traversable;

use function GoodPhp\Reflection\Util\Lazy\lazy;

class BuiltInCoreDefinitionProvider implements DefinitionProvider
{
	/** @var array<string, Lazy<TypeDefinition>> */
	private readonly array $typeDefinitions;

	public function __construct()
	{
		$this->typeDefinitions = [
			Countable::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: Countable::class,
				fileName: null,
				builtIn: true,
				typeParameters: [],
				extends: [],
				methods: [
					new MethodDefinition(
						name: 'count',
						typeParameters: [],
						parameters: [],
						returnType: PrimitiveType::integer(),
					),
				]
			)),
			ArrayAccess::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: ArrayAccess::class,
				fileName: null,
				builtIn: true,
				typeParameters: [
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
				],
				extends: [],
				methods: [
					new MethodDefinition(
						name: 'offsetExists',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								),
								hasDefaultValue: false,
							),
						],
						returnType: PrimitiveType::boolean(),
					),
					new MethodDefinition(
						name: 'offsetGet',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								),
								hasDefaultValue: false,
							),
						],
						returnType: new NullableType(
							new TemplateType(
								name: 'TValue',
							)
						),
					),
					new MethodDefinition(
						name: 'offsetSet',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'offset',
								type: new NullableType(
									new TemplateType(
										name: 'TKey',
									)
								),
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'value',
								type: new TemplateType(
									name: 'TValue',
								),
								hasDefaultValue: false,
							),
						],
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'offsetUnset',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								),
								hasDefaultValue: false,
							),
						],
						returnType: VoidType::get(),
					),
				]
			)),
			Traversable::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: Traversable::class,
				fileName: null,
				builtIn: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
				],
				extends: [
					new NamedType('iterable', [
						new TemplateType(
							name: 'TKey',
						),
						new TemplateType(
							name: 'TValue',
						),
					]),
				],
				methods: [
					new MethodDefinition(
						name: 'count',
						typeParameters: [],
						parameters: [],
						returnType: PrimitiveType::integer(),
					),
				]
			)),
			Closure::class => lazy(fn () => new ClassTypeDefinition(
				qualifiedName: Closure::class,
				fileName: null,
				builtIn: true,
				anonymous: false,
				final: true,
				abstract: false,
				typeParameters: [
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
				],
				extends: null,
				implements: [
					new NamedType('callable', [
						new TemplateType(
							name: 'TReturn',
						),
						new ExpandedType(
							new TemplateType(
								name: 'TParameter',
							)
						),
					]),
				],
				uses: new UsedTraitsDefinition(),
				properties: [],
				methods: [],
			)),
		];
	}

	public function forType(string $type): ?TypeDefinition
	{
		return ($this->typeDefinitions[$type] ?? null)?->value();
	}
}
