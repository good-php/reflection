<?php

namespace GoodPhp\Reflection\Reflector\Reflection\TypeParameters;

use Illuminate\Support\Collection;

interface HasTypeParameters
{
	/**
	 * @return Collection<int, TypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection;
}
