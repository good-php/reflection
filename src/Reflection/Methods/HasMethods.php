<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Names\HasQualifiedName;

/**
 * @template-contravariant ReflectableType of object
 */
interface HasMethods extends HasQualifiedName
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
