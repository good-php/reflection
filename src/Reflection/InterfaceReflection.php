<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasTypeParameters<self<ReflectableType>>
 * @extends HasMethods<ReflectableType>
 */
interface InterfaceReflection extends TypeReflection, HasAttributes, HasTypeParameters, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return Collection<int, NamedType>
	 */
	public function extends(): Collection;

	public function isBuiltIn(): bool;
}
