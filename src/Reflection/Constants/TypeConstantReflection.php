<?php

namespace GoodPhp\Reflection\Reflection\Constants;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @template-contravariant ReflectableType of object
 */
interface TypeConstantReflection extends Stringable, HasAttributes
{
	public function withStaticType(NamedType $staticType): static;

	public function name(): string;

	public function isFinal(): bool;

	public function type(): ?Type;

	public function typeSource(): ?TypeSource;

	public function value(): mixed;

	/**
	 * @return HasConstants<ReflectableType>
	 */
	public function declaringType(): HasConstants;
}
