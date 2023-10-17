<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Attribute;
use Illuminate\Support\Collection;

interface Attributes
{
	/**
	 * @param class-string<object> $className
	 */
	public function has(string $className): bool;

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return Collection<int, AttributeType>
	 */
	public function all(string $className = null): Collection;

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType> $className
	 *
	 * @return AttributeType|null
	 */
	public function sole(string $className): ?object;
}
