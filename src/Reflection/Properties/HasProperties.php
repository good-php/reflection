<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * @template ReflectableType of object
 *
 * @extends TypeReflection<ReflectableType>
 */
interface HasProperties extends TypeReflection
{
	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, $this>>
	 */
	public function declaredProperties(): Collection;

	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, HasProperties<object>>>
	 */
	public function properties(): Collection;
}
