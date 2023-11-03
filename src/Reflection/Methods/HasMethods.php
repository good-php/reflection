<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\HasName;
use GoodPhp\Reflection\Reflection\MethodReflection;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType of object
 */
interface HasMethods extends HasName
{
	/**
	 * @return Collection<int, MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): Collection;

	/**
	 * @return Collection<int, MethodReflection<ReflectableType, self<ReflectableType>>>
	 */
	public function methods(): Collection;
}
