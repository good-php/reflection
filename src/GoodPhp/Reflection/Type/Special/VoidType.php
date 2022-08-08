<?php

namespace GoodPhp\Reflection\Type\Special;

use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use GoodPhp\Reflection\Util\IsSingleton;

class VoidType implements Type
{
	use IsSingleton;
	use TypeExtensions;

	public function __toString(): string
	{
		return 'void';
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self;
	}

	public function traverse(callable $callback): Type
	{
		return $this;
	}
}
