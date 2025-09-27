<?php

namespace GoodPhp\Reflection\Reflection\Enums;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\EnumReflection;
use Stringable;
use UnitEnum;

/**
 * @template ReflectableType of UnitEnum
 * @template BackingValueType of string|int|null = string|int|null
 */
interface EnumCaseReflection extends Stringable, HasAttributes
{
	public function name(): string;

	/**
	 * @return BackingValueType
	 */
	public function backingValue(): string|int|null;

	/**
	 * Enum instance. NOT it's backing value - notice the return type.
	 *
	 * @return ReflectableType
	 */
	public function value(): UnitEnum;

	/**
	 * @return EnumReflection<ReflectableType, BackingValueType>
	 */
	public function declaringEnum(): EnumReflection;
}
