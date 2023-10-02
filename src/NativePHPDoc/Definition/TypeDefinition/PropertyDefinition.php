<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Type\Type;

final class PropertyDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?Type $type,
		public readonly bool $hasDefaultValue,
		public readonly bool $isPromoted,
	) {}
}
