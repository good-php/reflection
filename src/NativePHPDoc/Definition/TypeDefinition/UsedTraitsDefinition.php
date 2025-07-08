<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

final class UsedTraitsDefinition
{
	/**
	 * @param list<UsedTraitDefinition>         $traits
	 * @param array<class-string, list<string>> $excludedTraitMethods
	 */
	public function __construct(
		public readonly array $traits = [],
		public readonly array $excludedTraitMethods = [],
	) {}
}
