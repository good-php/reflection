<?php

namespace Tests\Stubs\Classes;

use IteratorAggregate;
use Traversable;

class CollectionStub implements IteratorAggregate
{
	public function getIterator(): Traversable
	{
		yield from [];
	}
}
