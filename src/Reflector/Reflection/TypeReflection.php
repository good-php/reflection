<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Str;
use JiriPudil\SealedClasses\Sealed;
use Stringable;

/**
 * @template-covariant T
 */
#[Sealed(permits: [ClassReflection::class, InterfaceReflection::class, TraitReflection::class, EnumReflection::class, SpecialTypeReflection::class])]
abstract class TypeReflection implements Stringable
{
	abstract public function qualifiedName(): string;

	abstract public function type(): NamedType;

	public function shortName(): string
	{
		return Str::afterLast($this->qualifiedName(), '\\');
	}

	public function location(): string
	{
		return $this->qualifiedName();
	}

	public function __toString(): string
	{
		return $this->shortName();
	}
}
