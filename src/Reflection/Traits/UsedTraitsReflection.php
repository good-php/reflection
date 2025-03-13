<?php

namespace GoodPhp\Reflection\Reflection\Traits;

interface UsedTraitsReflection
{
	/**
	 * @return list<UsedTraitReflection>
	 */
	public function traits(): array;

	/**
	 * @return array<class-string, list<string>>
	 */
	public function excludedTraitMethods(): array;
}
