<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 */
interface TypeParameterReflection extends Stringable
{
	public function name(): string;

	public function variadic(): bool;

	public function upperBound(): Type;

	public function variance(): TemplateTypeVariance;
}
