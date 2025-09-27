<?php

namespace Tests\Stubs\Classes;

use ArrayAccess;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, SomeStub>
 * @implements ArrayAccess<string, SomeStub>
 */
class CollectionStub implements IteratorAggregate, ArrayAccess
{
	public const MAX_ITEMS = PHP_INT_MAX;

	public function getIterator(): Traversable
	{
		yield from [];
	}

	public function offsetExists(mixed $offset): bool
	{
	}

	public function offsetGet(mixed $offset): mixed
	{
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
	}

	public function offsetUnset(mixed $offset): void
	{
	}
}
