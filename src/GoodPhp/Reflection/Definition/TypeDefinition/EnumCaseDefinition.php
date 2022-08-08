<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

final class EnumCaseDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly string|int|null $backingValue,
	) {
	}
}
