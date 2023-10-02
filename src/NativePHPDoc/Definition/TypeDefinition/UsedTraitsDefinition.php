<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use Illuminate\Support\Collection;

final class UsedTraitsDefinition
{
	/**
	 * @param Collection<int, UsedTraitDefinition>              $traits
	 * @param Collection<class-string, Collection<int, string>> $excludedTraitMethods
	 */
	public function __construct(
		public readonly Collection $traits = new Collection(),
		public readonly Collection $excludedTraitMethods = new Collection(),
	) {}
}
