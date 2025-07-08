<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 */
interface HasTypeParameters
{
	/**
	 * @return list<TypeParameterReflection<DeclaringStructureReflection>>
	 */
	public function typeParameters(): array;
}
