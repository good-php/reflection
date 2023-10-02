<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Type\Type;

final class FunctionParameterDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?Type $type,
		public readonly bool $hasDefaultValue,
	) {}
}
