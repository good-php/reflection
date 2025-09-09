<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\MethodReflection;
use Illuminate\Support\Arr;

/**
 * @template ReflectableType of object
 */
trait HasMethodsDefaults
{
	/**
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	abstract public function methods(): array;

	/**
	 * @return MethodReflection<ReflectableType, HasMethods<ReflectableType>>|null
	 */
	public function method(string $name): ?MethodReflection
	{
		return Arr::first($this->methods(), fn (MethodReflection $method) => $name === $method->name());
	}
}
