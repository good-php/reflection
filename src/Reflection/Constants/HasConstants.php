<?php

namespace GoodPhp\Reflection\Reflection\Constants;

use GoodPhp\Reflection\Reflection\Names\HasQualifiedName;

/**
 * @template-contravariant ReflectableType of object
 */
interface HasConstants extends HasQualifiedName
{
	/**
	 * @return list<TypeConstantReflection<ReflectableType>>
	 */
	public function declaredConstants(): array;

	/**
	 * @return list<TypeConstantReflection<ReflectableType>>
	 */
	public function constants(): array;

	/**
	 * @return TypeConstantReflection<ReflectableType>|null
	 */
	public function constant(string $name): ?TypeConstantReflection;
}
