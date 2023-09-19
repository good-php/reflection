<?php

namespace GoodPhp\Reflection\Reflector\Reflection\Attributes;

use Illuminate\Support\Collection;

interface HasAttributes
{
	public function attributes(): Attributes;
}
