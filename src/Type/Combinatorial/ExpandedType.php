<?php

namespace GoodPhp\Reflection\Type\Combinatorial;

use GoodPhp\Reflection\Type\Type;

class ExpandedType implements Type
{
	public function __construct(
		public readonly Type $innerType
	) {}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->innerType->equals($this->innerType);
	}

	public function traverse(callable $callback): Type
	{
		$newInnerType = $callback($this->innerType);

		if ($this->innerType !== $newInnerType) {
			return new self($newInnerType);
		}

		return $this;
	}

	public function __toString(): string
	{
		return "...{$this->innerType}";
	}
}
