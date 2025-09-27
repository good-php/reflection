<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\Type;

final class FunctionParameterDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?string $description,
		public readonly bool $passedByReference,
		public readonly ?Type $type,
		public readonly ?TypeSource $typeSource,
		public readonly bool $hasDefaultValue,
	) {}
}
