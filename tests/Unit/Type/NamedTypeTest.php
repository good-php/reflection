<?php

namespace Tests\Unit\Type;

use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class NamedTypeTest extends TestCase
{
	#[DataProvider('toStringProvider')]
	public function testToString(string $expected, NamedType $type): void
	{
		self::assertSame($expected, (string) $type);
	}

	public static function toStringProvider(): iterable
	{
		yield ['int', PrimitiveType::integer()];

		yield ['array<int|string, stdClass>', PrimitiveType::array(stdClass::class)];
	}
}
