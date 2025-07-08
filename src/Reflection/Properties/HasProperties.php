<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\HasName;
use GoodPhp\Reflection\Reflection\PropertyReflection;

/**
 * @template ReflectableType of object
 */
interface HasProperties extends HasName
{
	/**
	 * @return list<PropertyReflection<ReflectableType, $this>>
	 */
	public function declaredProperties(): array;

	/**
	 * @return list<PropertyReflection<ReflectableType, self<ReflectableType>>>
	 */
	public function properties(): array;
}
