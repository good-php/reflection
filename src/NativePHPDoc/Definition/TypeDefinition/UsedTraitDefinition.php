<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

final class UsedTraitDefinition
{
	/**
	 * @param Collection<int, UsedTraitAliasDefinition> $aliases
	 */
	public function __construct(
		public readonly NamedType $trait,
		public readonly Collection $aliases = new Collection(),
	) {}
}
