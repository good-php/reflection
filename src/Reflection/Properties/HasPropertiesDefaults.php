<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\PropertyReflection;
use Illuminate\Support\Arr;

/**
 * @template ReflectableType of object
 */
trait HasPropertiesDefaults
{
	/**
	 * @return list<PropertyReflection<ReflectableType>>
	 */
	abstract public function properties(): array;

	/**
	 * @return PropertyReflection<ReflectableType>|null
	 */
	public function property(string $name): ?PropertyReflection
	{
		return Arr::first($this->properties(), fn (PropertyReflection $property) => $name === $property->name());
	}
}
