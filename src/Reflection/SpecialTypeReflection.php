<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\Type;

/**
 * @template ReflectableType
 *
 * @extends TypeReflection<ReflectableType>
 */
interface SpecialTypeReflection extends TypeReflection, HasTypeParameters
{
	/**
	 * @return list<Type>
	 */
	public function superTypes(): array;
}
