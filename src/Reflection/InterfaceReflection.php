<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Constants\HasConstants;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasConstants<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface InterfaceReflection extends TypeReflection, HasAttributes, HasTypeParameters, HasConstants, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return list<NamedType>
	 */
	public function extends(): array;
}
