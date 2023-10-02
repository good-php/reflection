<?php

namespace GoodPhp\Reflection\Reflection\Traits;

use Illuminate\Support\Collection;

interface UsedTraitsReflection
{
	/**
	 * @return Collection<int, UsedTraitReflection>
	 */
	public function traits(): Collection;

	/**
	 * @return Collection<class-string, Collection<int, string>>
	 */
	public function excludedTraitMethods(): Collection;
}
