<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\Names\HasQualifiedName;
use GoodPhp\Reflection\Reflection\PropertyReflection;

/**
 * @template-contravariant ReflectableType of object
 */
interface HasProperties extends HasQualifiedName
{
	/**
	 * @return list<PropertyReflection<ReflectableType>>
	 */
	public function declaredProperties(): array;

	/**
	 * @return list<PropertyReflection<ReflectableType>>
	 */
	public function properties(): array;

	/**
	 * @return PropertyReflection<ReflectableType>|null
	 */
	public function property(string $name): ?PropertyReflection;
}
