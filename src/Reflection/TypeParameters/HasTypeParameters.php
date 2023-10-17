<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

use Illuminate\Support\Collection;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 */
interface HasTypeParameters
{
	/**
	 * @return Collection<int, TypeParameterReflection<DeclaringStructureReflection>>
	 */
	public function typeParameters(): Collection;
}
