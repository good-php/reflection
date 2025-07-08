<?php

namespace GoodPhp\Reflection\Type\Combinatorial;

use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use GoodPhp\Reflection\Type\TypeUtil;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class UnionType implements Type
{
	use TypeExtensions;

	/**
	 * @param list<Type> $types
	 */
	public function __construct(
		public array $types,
	) {
		// Transform A|(B|C) into A|B|C
		/* @phpstan-ignore assign.propertyType */
		$this->types = array_reduce($types, function (array $accumulator, Type $type) {
			$types = $type instanceof self ? $type->types : [$type];

			return [...$accumulator, ...$types];
		}, []);

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

	public function withoutType(Type|callable $filter): Type
	{
		$filter = $filter instanceof Type ? fn (Type $other) => $other->equals($filter) : $filter;

		$types = array_values(
			array_filter($this->types, fn (Type $type) => !$filter($type))
		);

		/** @var Type */
		return match (count($types)) {
			0       => NeverType::get(),
			1       => Arr::first($types),
			default => new self($types)
		};
	}

	public function __toString(): string
	{
		$types = array_map(
			fn (Type $type) => $type instanceof self || $type instanceof IntersectionType || $type instanceof NullableType ? "({$type})" : (string) $type,
			$this->types
		);

		return implode('|', $types);
	}
}
