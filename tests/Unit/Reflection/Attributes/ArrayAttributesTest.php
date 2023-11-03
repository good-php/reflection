<?php

namespace Tests\Unit\Reflection\Attributes;

use GoodPhp\Reflection\Reflection\Attributes\ArrayAttributes;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AttributeStub;

class ArrayAttributesTest extends TestCase
{
	#[DataProvider('allEqualProvider')]
	public function testAllEqual(bool $expected, ArrayAttributes $first, Attributes $second): void
	{
		$result = $first->allEqual($second);

		self::assertSame($expected, $result);
	}

	public static function allEqualProvider(): iterable
	{
		yield [
			true, new ArrayAttributes(), new ArrayAttributes(),
		];

		yield [
			true,
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123')],
			]),
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123')],
			]),
		];

		yield [
			false,
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123')],
			]),
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('456')],
			]),
		];

		yield [
			true,
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123'), new AttributeStub('456')],
			]),
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123'), new AttributeStub('456')],
			]),
		];

		yield [
			false,
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123'), new AttributeStub('456')],
			]),
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('456')],
			]),
		];

		yield [
			false,
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123'), new AttributeStub('456')],
			]),
			new ArrayAttributes([
				AttributeStub::class => [new AttributeStub('123'), new AttributeStub('456'), new AttributeStub('789')],
			]),
		];
	}
}
