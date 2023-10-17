<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType
 *
 * @extends TypeReflection<ReflectableType>
 * @extends HasTypeParameters<self<ReflectableType>>
 */
interface SpecialTypeReflection extends TypeReflection, HasTypeParameters
{
	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection;
}
