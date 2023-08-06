<?php

namespace GoodPhp\Reflection\Type\Combinatorial;

use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use GoodPhp\Reflection\Type\TypeUtil;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

class UnionType implements Type
{
	use TypeExtensions;

	/**
	 * @param Collection<int, Type> $types
	 */
	public function __construct(
		public Collection $types,
	) {
		// Transform A|(B|C) into A|B|C
		$this->types = $types->reduce(function (Collection $accumulator, Type $type) {
			$types = $type instanceof self ? $type->types : [$type];

			return $accumulator->concat($types);
		}, new Collection());

		Assert::minCount($this->types, 2);
	}

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
		return $this->types
			->map(fn (Type $type) => $type instanceof self || $type instanceof IntersectionType || $type instanceof NullableType ? "({$type})" : (string) $type)
			->join('|');
	}
}
