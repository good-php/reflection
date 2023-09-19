<?php

namespace GoodPhp\Reflection\Type;

use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use Illuminate\Support\Collection;

final class PrimitiveType
{
	/** @var NamedType<object> */
	private static NamedType $object;

	/** @var NamedType<string> */
	private static NamedType $string;

	/** @var NamedType<bool> */
	private static NamedType $boolean;

	/** @var NamedType<int> */
	private static NamedType $integer;

	/** @var NamedType<float> */
	private static NamedType $float;

	private static UnionType $arrayKey;

	/**
	 * @return NamedType<mixed>
	 */
	public static function callable(Type $returnType, Type ...$parameters): NamedType
	{
		return new NamedType('callable', new Collection([
			$returnType,
			...$parameters,
		]));
	}

	/**
	 * @return NamedType<mixed>
	 */
	public static function array(Type|string $value, Type|string $key = null): NamedType
	{
		return new NamedType('array', new Collection([
			$key ?? self::arrayKey(),
			$value,
		]));
	}

	/**
	 * @return NamedType<mixed>
	 */
	public static function iterable(Type|string $value, Type|string $key = null): NamedType
	{
		return new NamedType('iterable', new Collection([
			$key ?? self::arrayKey(),
			$value,
		]));
	}

	/**
	 * @return NamedType<object>
	 */
	public static function object(): NamedType
	{
		return self::$object ??= new NamedType('object');
	}

	/**
	 * @return NamedType<string>
	 */
	public static function string(): NamedType
	{
		return self::$string ??= new NamedType('string');
	}

	/**
	 * @return NamedType<bool>
	 */
	public static function boolean(): NamedType
	{
		return self::$boolean ??= new NamedType('bool');
	}

	/**
	 * @return NamedType<int>
	 */
	public static function integer(): NamedType
	{
		return self::$integer ??= new NamedType('int');
	}

	/**
	 * @return NamedType<float>
	 */
	public static function float(): NamedType
	{
		return self::$float ??= new NamedType('float');
	}

	private static function arrayKey(): UnionType
	{
		return self::$arrayKey ??= new UnionType(
			new Collection([
				self::integer(),
				self::string(),
			]),
		);
	}
}
