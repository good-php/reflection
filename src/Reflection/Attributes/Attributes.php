<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Illuminate\Support\Collection;
use Stringable;

interface Attributes extends Stringable
{
	/**
	 * @param class-string<object>|null $className
	 */
	public function has(string $className = null): bool;

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return ($className is null ? Collection<int, object> : Collection<int, AttributeType>)
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

	public function allEqual(self $attributes): bool;
}
