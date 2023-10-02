<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

final class UsedTraitAliasDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?string $newName = null,
		public readonly ?int $newModifier = null,
	) {}
}
