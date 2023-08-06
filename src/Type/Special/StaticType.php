<?php

namespace GoodPhp\Reflection\Type\Special;

use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;

class StaticType implements Type
{
	use TypeExtensions;

	public function __construct(
		public readonly Type $baseType,
	) {}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->baseType->equals($this->baseType);
	}

	public function traverse(callable $callback): Type
	{
		$newBaseType = $callback($this->baseType);

		if ($this->baseType !== $newBaseType) {
			return new self($newBaseType);
		}

		return $this;
	}

	public function __toString(): string
	{
		return "static<{$this->baseType}>";
	}
}
