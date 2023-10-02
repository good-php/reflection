<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Attribute;
use Illuminate\Support\Collection;

interface Attributes
{
	/**
	 * @param class-string<Attribute> $className
	 */
	public function has(string $className): bool;

	/**
	 * @template AttributeType of \Attribute
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return Collection<int, AttributeType>
	 */
	public function all(string $className = null): Collection;

	/**
	 * @template AttributeType of \Attribute
	 *
	 * @param class-string<AttributeType> $className
	 *
	 * @return Attribute|null
	 */
	public function sole(string $className): ?object;
}
