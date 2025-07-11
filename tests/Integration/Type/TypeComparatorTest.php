<?php

namespace Tests\Integration\Type;

use Closure;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeComparator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Integration\IntegrationTestCase;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\ParentClassStub;
use Tests\Stubs\Classes\SomeStub;

/**
 * @see TypeComparator
 */
class TypeComparatorTest extends IntegrationTestCase
{
	private TypeComparator $comparator;

	protected function setUp(): void
	{
		parent::setUp();

		$this->comparator = $this->reflector->typeComparator();
	}

	#[DataProvider('acceptsProvider')]
	#[DataProvider('acceptsUnionProvider')]
	#[DataProvider('acceptsErrorProvider')]
	#[DataProvider('acceptsMixedProvider')]
	#[DataProvider('acceptsNeverProvider')]
	#[DataProvider('acceptsNullableProvider')]
	#[DataProvider('acceptsStaticProvider')]
	#[DataProvider('acceptsVoidProvider')]
	#[DataProvider('acceptsTemplateProvider')]
	#[DataProvider('acceptsNamedProvider')]
	public function testAccepts(bool $expected, Type $a, Type $b): void
	{
		self::assertSame(
			$expected,
			$this->comparator->accepts($a, $b),
			"Type {$a} does " . ($expected ? 'not ' : '') . "accept type {$b}"
		);
	}

	public static function acceptsProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsUnionProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsErrorProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsMixedProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsNeverProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsNullableProvider(): iterable
	{
		yield '?string <= ?string' => [
			true,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= string' => [
			true,
			new NullableType(PrimitiveType::string()),
			PrimitiveType::string(),
		];

		yield 'string <= ?string' => [
			false,
			PrimitiveType::string(),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= ?int' => [
			false,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::integer()),
		];

		yield '?int <= ?string' => [
			false,
			new NullableType(PrimitiveType::integer()),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= string|int|null' => [
			false,
			new NullableType(PrimitiveType::string()),
			new NullableType(new UnionType([PrimitiveType::string(), PrimitiveType::integer()])),
		];

		yield 'string|int|null <= string' => [
			true,
			new NullableType(new UnionType([PrimitiveType::string(), PrimitiveType::integer()])),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= (?string)|int' => [
			false,
			new NullableType(PrimitiveType::string()),
			new UnionType([new NullableType(PrimitiveType::string()), PrimitiveType::integer()]),
		];

		yield '(?string)|int <= ?string' => [
			true,
			new UnionType([new NullableType(PrimitiveType::string()), PrimitiveType::integer()]),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= ?(string&integer)' => [
			true,
			new NullableType(PrimitiveType::string()),
			new NullableType(new IntersectionType([PrimitiveType::string(), PrimitiveType::integer()])),
		];

		yield '?(string&integer) <= ?string' => [
			false,
			new NullableType(new IntersectionType([PrimitiveType::string(), PrimitiveType::integer()])),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= (?string)&integer' => [
			true,
			new NullableType(PrimitiveType::string()),
			new IntersectionType([new NullableType(PrimitiveType::string()), PrimitiveType::integer()]),
		];

		yield '(?string)&integer <= ?string' => [
			false,
			new IntersectionType([new NullableType(PrimitiveType::string()), PrimitiveType::integer()]),
			new NullableType(PrimitiveType::string()),
		];
	}

	public static function acceptsStaticProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsVoidProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsTemplateProvider(): iterable
	{
		yield from [];
	}

	public static function acceptsNamedProvider(): iterable
	{
		yield 'SomeStub <= SomeStub' => [
			true,
			new NamedType(SomeStub::class),
			new NamedType(SomeStub::class),
		];

		yield 'ParentClassStub <= SomeStub' => [
			false,
			new NamedType(ParentClassStub::class),
			new NamedType(SomeStub::class),
		];

		yield 'SomeStub <= ParentClassStub' => [
			false,
			new NamedType(SomeStub::class),
			new NamedType(ParentClassStub::class),
		];

		yield 'ClassStub<SomeStub> <= ClassStub<SomeStub>' => [
			true,
			new NamedType(ClassStub::class, [
				new NamedType(SomeStub::class),
				PrimitiveType::integer(),
			]),
			new NamedType(ClassStub::class, [
				new NamedType(SomeStub::class),
				PrimitiveType::integer(),
			]),
		];

		yield 'callable(int): float <= Closure(float): int' => [
			true,
			new NamedType('callable', [
				PrimitiveType::float(),
				PrimitiveType::integer(),
			]),
			new NamedType(Closure::class, [
				PrimitiveType::integer(),
				PrimitiveType::float(),
			]),
		];

		yield 'callable(float): int <= Closure(int): float' => [
			false,
			new NamedType('callable', [
				PrimitiveType::integer(),
				PrimitiveType::float(),
			]),
			new NamedType(Closure::class, [
				PrimitiveType::float(),
				PrimitiveType::integer(),
			]),
		];
	}
}
