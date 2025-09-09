<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\HasName;
use GoodPhp\Reflection\Reflection\MethodReflection;

/**
 * @template ReflectableType of object
 */
interface HasMethods extends HasName
{
	/**
	 * @return list<MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): array;

	/**
	 * @return list<MethodReflection<ReflectableType, self<ReflectableType>>>
	 */
	public function methods(): array;

	/**
	 * @return MethodReflection<ReflectableType, self<ReflectableType>>|null
	 */
	public function method(string $name): ?MethodReflection;
}
