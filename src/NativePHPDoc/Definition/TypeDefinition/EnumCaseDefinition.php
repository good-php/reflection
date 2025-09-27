<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

final class EnumCaseDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?string $description,
		public readonly string|int|null $backingValue,
	) {}
}
