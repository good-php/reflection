<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

interface HasTypeParameters
{
	/**
	 * @return list<TypeParameterReflection>
	 */
	public function typeParameters(): array;

	public function typeParameter(string $name): ?TypeParameterReflection;
}
