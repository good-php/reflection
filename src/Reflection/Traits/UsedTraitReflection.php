<?php

namespace GoodPhp\Reflection\Reflection\Traits;

use GoodPhp\Reflection\Type\NamedType;

interface UsedTraitReflection
{
	public function trait(): NamedType;

	/**
	 * @return list<UsedTraitAliasReflection>
	 */
	public function aliases(): array;
}
