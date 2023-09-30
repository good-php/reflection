<?php

namespace GoodPhp\Reflection\Type\Special;

use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;

class StaticType implements Type
{
	use TypeExtensions;

	public function __construct(
		public readonly Type $upperBound,
	) {}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->upperBound->equals($this->upperBound);
	}

	public function traverse(callable $callback): Type
	{
		$newUpperBound = $callback($this->upperBound);

		if ($this->upperBound !== $newUpperBound) {
			return new self($newUpperBound);
		}

		return $this;
	}

	public function __toString(): string
	{
		return "static<{$this->upperBound}>";
	}
}
