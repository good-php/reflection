<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasTypeParameters<self<ReflectableType>>
 * @extends HasProperties<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface TraitReflection extends TypeReflection, HasAttributes, HasTypeParameters, HasProperties, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	public function uses(): UsedTraitsReflection;

	public function isBuiltIn(): bool;
}
