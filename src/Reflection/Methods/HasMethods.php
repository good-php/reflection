<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\HasName;
use GoodPhp\Reflection\Reflection\MethodReflection;

/**
 * @template-contravariant ReflectableType of object
 */
interface HasMethods extends HasName
{
	/**
	 * @return list<MethodReflection<ReflectableType>>
	 */
	public function declaredMethods(): array;

	/**
	 * @return list<MethodReflection<ReflectableType>>
	 */
	public function methods(): array;

	/**
	 * @return MethodReflection<ReflectableType>|null
	 */
	public function method(string $name): ?MethodReflection;
}
