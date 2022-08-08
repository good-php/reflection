<?php

namespace Tests\Unit\GoodPhp\Reflection\Type\Special;

use Generator;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @see NullableType
 */
class NullableTypeTest extends TestCase
{
	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals(bool $expected, NullableType $first, Type $second): void
	{
		self::assertSame(
			$expected,
			$first->equals($second)
		);
	}

	public function equalsProvider(): Generator
	{
		yield 'exact same' => [
			true,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::string()),
		];

		yield 'different delegates' => [
			false,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::integer()),
		];

		yield 'not even nullable' => [
			false,
			new NullableType(PrimitiveType::string()),
			PrimitiveType::string(),
		];
	}

	/**
	 * @dataProvider stringRepresentationProvider
	 */
	public function testStringRepresentation(string $expected, Type $delegate): void
	{
		self::assertSame(
			$expected,
			(string) (new NullableType($delegate))
		);
	}

	public function stringRepresentationProvider(): Generator
	{
		yield 'simple' => [
			'?string',
			PrimitiveType::string(),
		];

		yield 'intersection' => [
			'?(int&float)',
			new IntersectionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			])),
		];

		yield 'union' => [
			'int|float|null',
			new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			])),
		];
	}
}
