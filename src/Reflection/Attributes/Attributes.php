<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Stringable;

interface Attributes extends Stringable
{
	/**
	 * @param class-string<object>|null $className
	 */
	public function has(?string $className = null): bool;

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return ($className is null ? list<object> : list<AttributeType>)
	 */
	public function all(?string $className = null): array;

	/**
	 * Returns exactly one attribute of specific type, or null.
	 *
	 * Throws an exception if there are two or more attributes of specified type.
	 *
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType> $className
	 *
	 * @return AttributeType|null
	 */
	public function sole(string $className): ?object;

	public function allEqual(self $attributes): bool;
}
