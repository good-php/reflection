<?php

namespace GoodPhp\Reflection\Reflection\Constants;

use Illuminate\Support\Arr;

/**
 * @template ReflectableType of object
 */
trait HasConstantsDefaults
{
	/**
	 * @return list<TypeConstantReflection<ReflectableType>>
	 */
	abstract public function constants(): array;

	/**
	 * @return TypeConstantReflection<ReflectableType>|null
	 */
	public function constant(string $name): ?TypeConstantReflection
	{
		return Arr::first($this->constants(), fn (TypeConstantReflection $constant) => $name === $constant->name());
	}
}
