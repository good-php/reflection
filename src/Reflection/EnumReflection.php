<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Constants\HasConstants;
use GoodPhp\Reflection\Reflection\Enums\EnumCaseReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template ReflectableType of \UnitEnum
 * @template BackingValueType of string|int|null = string|int|null
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasConstants<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface EnumReflection extends TypeReflection, HasAttributes, HasConstants, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return list<NamedType>
	 */
	public function implements(): array;

	public function uses(): UsedTraitsReflection;

	/**
	 * @return list<EnumCaseReflection<ReflectableType, BackingValueType>>
	 */
	public function cases(): array;

	/**
	 * @return EnumCaseReflection<ReflectableType, BackingValueType>|null
	 */
	public function case(string $name): ?EnumCaseReflection;
}
