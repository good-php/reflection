<?php

namespace Tests\Unit\Type\Special;

use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @see NullableType
 */
class NullableTypeTest extends TestCase
{
	#[DataProvider('equalsProvider')]
	public function testEquals(bool $expected, NullableType $first, Type $second): void
	{
		self::assertSame(
			$expected,
			$first->equals($second)
		);
	}

	public static function equalsProvider(): iterable
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

	#[DataProvider('stringRepresentationProvider')]
	public function testStringRepresentation(string $expected, Type $delegate): void
	{
		self::assertSame(
			$expected,
			(string) (new NullableType($delegate))
		);
	}

	public static function stringRepresentationProvider(): iterable
	{
		yield 'simple' => [
			'?string',
			PrimitiveType::string(),
		];

		yield 'intersection' => [
			'?(int&float)',
			new IntersectionType([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			]),
		];

		yield 'union' => [
			'int|float|null',
			new UnionType([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			]),
		];
	}
}
