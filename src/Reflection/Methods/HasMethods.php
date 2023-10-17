<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 */
interface HasMethods extends TypeReflection
{
	/**
	 * @return Collection<int, MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): Collection;

	/**
	 * @return Collection<int, MethodReflection<ReflectableType, HasMethods<object>>>
	 */
	public function methods(): Collection;
}
