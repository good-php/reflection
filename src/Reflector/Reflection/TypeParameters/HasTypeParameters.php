<?php

namespace GoodPhp\Reflection\Reflector\Reflection\TypeParameters;

use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use Illuminate\Support\Collection;

interface HasTypeParameters
{
	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection;
}
