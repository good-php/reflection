<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

/**
 * @template-covariant T
 *
 * @extends TypeReflection<T>
 */
interface SpecialTypeReflection extends TypeReflection, HasTypeParameters
{
	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection;
}
