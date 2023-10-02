<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;
use UnitEnum;

interface HasProperties
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return Collection<int, PropertyReflection<$this>>
	 */
	public function declaredProperties(): Collection;

	/**
	 * @return Collection<int, PropertyReflection<$this|ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>
	 */
	public function properties(): Collection;
}
