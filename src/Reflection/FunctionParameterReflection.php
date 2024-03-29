<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @template-covariant DeclaringMethodReflection of MethodReflection
 */
interface FunctionParameterReflection extends Stringable, HasAttributes
{
	public function name(): string;

	public function type(): ?Type;

	public function hasDefaultValue(): bool;

	public function defaultValue(): mixed;

	public function location(): string;

	/**
	 * @return DeclaringMethodReflection
	 */
	public function declaringMethod(): MethodReflection;
}
