<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Type\NamedType;
use JiriPudil\SealedClasses\Sealed;
use Stringable;

/**
 * @template-covariant ReflectableType
 */
#[Sealed(permits: [
	ClassReflection::class,
	InterfaceReflection::class,
	TraitReflection::class,
	EnumReflection::class,
	SpecialTypeReflection::class,
])]
interface TypeReflection extends HasName, Stringable
{
	public function withStaticType(NamedType $staticType): static;

	public function type(): NamedType;

	public function isBuiltIn(): bool;
}
