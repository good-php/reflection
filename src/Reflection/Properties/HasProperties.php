<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\HasName;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType of object
 */
interface HasProperties extends HasName
{
	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, $this>>
	 */
	public function declaredProperties(): Collection;

	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, self<ReflectableType>>>
	 */
	public function properties(): Collection;
}
