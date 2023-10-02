<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;
use UnitEnum;

interface HasMethods
{
	public function withStaticType(NamedType $staticType): static;

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection;

	/**
	 * @return Collection<int, MethodReflection<$this|ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>
	 */
	public function methods(): Collection;
}
