<?php

namespace GoodPhp\Reflection\Util;

use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Webmozart\Assert\Assert;

class ReflectionAssert
{
	/**
	 * @phpstan-assert NamedType $type
	 */
	public static function namedType(Type $type, string $when = ''): void
	{
		Assert::isInstanceOf(
			$type,
			NamedType::class,
			'Expected a named type (int, \stdClass, array<string, string>), but [' . $type . '] given' . ($when ? " {$when}" : '') . '.'
		);
	}
}
