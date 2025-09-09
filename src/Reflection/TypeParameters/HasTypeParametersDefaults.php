<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

use Illuminate\Support\Arr;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 */
trait HasTypeParametersDefaults
{
	/**
	 * @return list<TypeParameterReflection<DeclaringStructureReflection>>
	 */
	abstract public function typeParameters(): array;

	/**
	 * @return TypeParameterReflection<DeclaringStructureReflection>|null
	 */
	public function typeParameter(string|int $nameOrIndex): ?TypeParameterReflection
	{
		if (is_int($nameOrIndex)) {
			return $this->typeParameters()[$nameOrIndex] ?? null;
		}

		return Arr::first($this->typeParameters(), fn (TypeParameterReflection $typeParameter) => $nameOrIndex === $typeParameter->name());
	}
}
