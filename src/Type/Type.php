<?php

namespace GoodPhp\Reflection\Type;

use Stringable;

interface Type extends Stringable
{
	public function equals(self $other): bool;

	/**
	 * Traverses inner types
	 *
	 * Returns a new instance with all inner types mapped through $cb. Might
	 * return the same instance if inner types did not change.
	 *
	 * @param callable(self): self $callback
	 */
	public function traverse(callable $callback): self;
}
