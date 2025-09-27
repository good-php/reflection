<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Constants\HasConstants;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasConstants<ReflectableType>
 * @extends HasProperties<ReflectableType>
 * @extends HasMethods<ReflectableType>
 */
interface ClassReflection extends TypeReflection, HasAttributes, HasTypeParameters, HasConstants, HasProperties, HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	public function extends(): ?NamedType;

	/**
	 * @return list<NamedType>
	 */
	public function implements(): array;

	public function uses(): UsedTraitsReflection;

	/**
	 * @return MethodReflection<ReflectableType>|null
	 */
	public function constructor(): ?MethodReflection;

	public function isAnonymous(): bool;

	public function isAbstract(): bool;

	public function isFinal(): bool;

	public function isReadOnly(): bool;

	public function isCloneable(): bool;

	public function isInstantiable(): bool;

	/**
	 * @return ReflectableType
	 */
	public function newInstance(mixed ...$args): object;

	/**
	 * @return ReflectableType
	 */
	public function newInstanceWithoutConstructor(): object;
}
