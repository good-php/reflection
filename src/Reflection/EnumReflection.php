<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template ReflectableType of \UnitEnum
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface EnumReflection extends TypeReflection, HasAttributes, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return list<NamedType>
	 */
	public function implements(): array;

	public function uses(): UsedTraitsReflection;
}
