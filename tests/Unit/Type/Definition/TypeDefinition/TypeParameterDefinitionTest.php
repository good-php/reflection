<?php

namespace Tests\Unit\Type\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @see TypeParameterDefinition
 */
class TypeParameterDefinitionTest extends TestCase
{
	#[DataProvider('toStringProvider')]
	public function testToString(string $expected, TypeParameterDefinition $parameter): void
	{
		self::assertSame(
			$expected,
			(string) $parameter,
		);
	}

	public static function toStringProvider(): iterable
	{
		yield [
			'T',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'T Text',
			new TypeParameterDefinition(
				name: 'T',
				description: 'Text',
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'contravariant T',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::CONTRAVARIANT,
			),
		];

		yield [
			'covariant T',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::COVARIANT,
			),
		];

		yield [
			'...T',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: true,
				upperBound: null,
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'contravariant ...T',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: true,
				upperBound: null,
				variance: TemplateTypeVariance::CONTRAVARIANT,
			),
		];

		yield [
			'T of int',
			new TypeParameterDefinition(
				name: 'T',
				description: null,
				variadic: false,
				upperBound: PrimitiveType::integer(),
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'covariant ...T of int Text',
			new TypeParameterDefinition(
				name: 'T',
				description: 'Text',
				variadic: true,
				upperBound: PrimitiveType::integer(),
				variance: TemplateTypeVariance::COVARIANT,
			),
		];

		yield [
			'covariant T of mixed = int Text',
			new TypeParameterDefinition(
				name: 'T',
				description: 'Text',
				variadic: false,
				upperBound: MixedType::get(),
				variance: TemplateTypeVariance::COVARIANT,
				default: PrimitiveType::integer(),
			),
		];
	}
}
