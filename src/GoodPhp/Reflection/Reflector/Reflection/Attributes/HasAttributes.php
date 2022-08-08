<?php

namespace GoodPhp\Reflection\Reflector\Reflection\Attributes;

use Illuminate\Support\Collection;

interface HasAttributes
{
	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection;
}
