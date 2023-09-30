<?php

namespace GoodPhp\Reflection\Type;

use GoodPhp\Reflection\Type\Combinatorial\ExpandedType;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\Special\ErrorType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use JiriPudil\SealedClasses\Sealed;
use Stringable;

#[Sealed(permits: [
	ExpandedType::class,
	IntersectionType::class,
	TupleType::class,
	UnionType::class,
	ErrorType::class,
	MixedType::class,
	NeverType::class,
	NullableType::class,
	StaticType::class,
	VoidType::class,
	TemplateType::class,
	NamedType::class,
])]
interface Type extends Stringable
{
	public function equals(self $other): bool;

	/**
	 * Traverses inner types
	 *
	 * Returns a new instance with all inner types mapped through $cb. Might
	 * return the same instance if inner types did not change.
	 *
	 * @param callable(self): self $callback
	 */
	public function traverse(callable $callback): self;
}
