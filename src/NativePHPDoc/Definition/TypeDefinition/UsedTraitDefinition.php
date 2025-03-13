<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Type\NamedType;

final class UsedTraitDefinition
{
	/**
	 * @param list<UsedTraitAliasDefinition> $aliases
	 */
	public function __construct(
		public readonly NamedType $trait,
		public readonly array $aliases = [],
	) {}
}
