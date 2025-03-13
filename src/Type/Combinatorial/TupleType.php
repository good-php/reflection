<?php

namespace GoodPhp\Reflection\Type\Combinatorial;

use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use GoodPhp\Reflection\Type\TypeUtil;

class TupleType implements Type
{
	use TypeExtensions;

	/**
	 * @param list<Type> $types
	 */
	public function __construct(
		public array $types,
	) {}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			TypeUtil::allEqual($other->types, $this->types);
	}

	public function traverse(callable $callback): Type
	{
		$changed = false;

		$types = array_map(function (Type $type) use ($callback, &$changed) {
			$newType = $callback($type);

			if ($type !== $newType) {
				$changed = true;
			}

			return $newType;
		}, $this->types);

		if ($changed) {
			return new self($types);
		}

		return $this;
	}

	public function __toString(): string
	{
		$types = array_map(
			fn (Type $type) => (string) $type,
			$this->types
		);

		$types = implode(', ', $types);

		return "array{{$types}}";
	}
}
