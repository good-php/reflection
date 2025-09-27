<?php

namespace GoodPhp\Reflection\Reflection\TypeParameters;

use GoodPhp\Reflection\Reflection\Descriptions\HasDescription;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Type;
use Stringable;

interface TypeParameterReflection extends Stringable, HasDescription
{
	public function name(): string;

	public function variadic(): bool;

	public function upperBound(): Type;

	public function variance(): TemplateTypeVariance;
}
