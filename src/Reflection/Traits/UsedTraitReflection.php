<?php

namespace GoodPhp\Reflection\Reflection\Traits;

use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

interface UsedTraitReflection
{
	public function trait(): NamedType;

	/**
	 * @return Collection<int, UsedTraitAliasReflection>
	 */
	public function aliases(): Collection;
}
