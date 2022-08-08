<?php

namespace GoodPhp\Reflection\Type\Special;

use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeExtensions;
use Tests\Unit\TenantCloud\BetterReflection\Type\Special\NullableTypeTest;

/**
 * @template-covariant T of Type
 *
 * PHPStan handles this with UnionType(delegate, NullType). We've decided not to do this:
 *  - null can't be a standalone type. Not much sense to have it as a standalone then.
 *  - to check if type is nullable just do instanceof. Much better than checking if union contains null.
 *  - represents null better - the way it's intended in PHP with "?" symbol prefixing the type.
 *
 * @see NullableTypeTest
 */
class NullableType implements Type
{
	use TypeExtensions;

	public function __construct(
		public readonly Type $innerType
	) {
	}

	public function __toString(): string
	{
		if ($this->innerType instanceof UnionType) {
			return $this->innerType . '|null';
		}

		if ($this->innerType instanceof IntersectionType) {
			return "?({$this->innerType})";
		}

		return "?{$this->innerType}";
	}

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
}
