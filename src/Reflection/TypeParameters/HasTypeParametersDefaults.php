<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

use Illuminate\Support\Arr;

trait HasTypeParametersDefaults
{
	/**
	 * @return list<TypeParameterReflection>
	 */
	abstract public function typeParameters(): array;

	public function typeParameter(string|int $nameOrIndex): ?TypeParameterReflection
	{
		if (is_int($nameOrIndex)) {
			return $this->typeParameters()[$nameOrIndex] ?? null;
		}

		return Arr::first($this->typeParameters(), fn (TypeParameterReflection $typeParameter) => $nameOrIndex === $typeParameter->name());
	}
}
