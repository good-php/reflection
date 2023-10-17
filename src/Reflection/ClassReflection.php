<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasTypeParameters<self<ReflectableType>>
 * @extends HasProperties<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface ClassReflection extends TypeReflection, HasAttributes, HasTypeParameters, HasProperties, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	public function extends(): ?NamedType;

	/**
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection;

	public function uses(): UsedTraitsReflection;

	/**
	 * @return MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>|null
	 */
	public function constructor(): ?MethodReflection;

	public function isAnonymous(): bool;

	public function isAbstract(): bool;

	public function isFinal(): bool;

	public function isBuiltIn(): bool;

	/**
	 * @return T
	 */
	public function newInstance(mixed ...$args): object;

	/**
	 * @return T
	 */
	public function newInstanceWithoutConstructor(): object;
}
