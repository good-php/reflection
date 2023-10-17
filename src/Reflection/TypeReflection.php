<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Type\NamedType;
use JiriPudil\SealedClasses\Sealed;
use Stringable;

/**
 * @template ReflectableType
 */
#[Sealed(permits: [
	ClassReflection::class,
	InterfaceReflection::class,
	TraitReflection::class,
	EnumReflection::class,
	SpecialTypeReflection::class,
])]
interface TypeReflection extends Stringable
{
	public function withStaticType(NamedType $staticType): static;

	public function fileName(): ?string;

	public function qualifiedName(): string;

	public function shortName(): string;

	public function type(): NamedType;

	public function location(): string;
}
