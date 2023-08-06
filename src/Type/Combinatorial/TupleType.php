<?php

namespace GoodPhp\Reflection\Type\Combinatorial;

use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use GoodPhp\Reflection\Type\TypeUtil;
use Illuminate\Support\Collection;

class TupleType implements Type
{
	use TypeExtensions;

	/**
	 * @param Collection<int, Type> $types
	 */
	public function __construct(
		public Collection $types,
	) {}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			TypeUtil::allEqual($other->types, $this->types);
	}

	public function traverse(callable $callback): Type
	{
		$changed = false;

		$types = $this->types
			->map(function (Type $type) use ($callback, &$changed) {
				$newType = $callback($type);

				if ($type !== $newType) {
					$changed = true;
				}

				return $newType;
			});

		if ($changed) {
			return new self($types);
		}

		return $this;
	}

	public function __toString(): string
	{
		$types = $this->types
			->map(fn (Type $type) => (string) $type)
			->join(', ');

		return "array{{$types}}";
	}
}
