<?php

namespace GoodPhp\Reflection\Type;

use GoodPhp\Reflection\Type\Combinatorial\UnionType;

final class PrimitiveType
{
	private static NamedType $object;

	private static NamedType $string;

	private static NamedType $boolean;

	private static NamedType $integer;

	private static NamedType $float;

	private static UnionType $arrayKey;

	public static function callable(Type|string $returnType, Type|string ...$parameters): NamedType
	{
		/** @var array<int, Type|string> $parameters */

		return NamedType::wrap('callable', [
			$returnType,
			...$parameters,
		]);
	}

	public static function array(Type|string $value, Type|string|null $key = null): NamedType
	{
		return NamedType::wrap('array', [
			$key ?? self::arrayKey(),
			$value,
		]);
	}

	public static function iterable(Type|string $value, Type|string|null $key = null): NamedType
	{
		return NamedType::wrap('iterable', [
			$key ?? self::arrayKey(),
			$value,
		]);
	}

	public static function object(): NamedType
	{
		return self::$object ??= new NamedType('object');
	}

	public static function string(): NamedType
	{
		return self::$string ??= new NamedType('string');
	}

	public static function boolean(): NamedType
	{
		return self::$boolean ??= new NamedType('bool');
	}

	public static function integer(): NamedType
	{
		return self::$integer ??= new NamedType('int');
	}

	public static function float(): NamedType
	{
		return self::$float ??= new NamedType('float');
	}

	private static function arrayKey(): UnionType
	{
		return self::$arrayKey ??= new UnionType([
			self::integer(),
			self::string(),
		]);
	}
}
