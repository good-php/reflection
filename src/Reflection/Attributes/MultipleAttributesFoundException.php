<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use RuntimeException;
use Throwable;

class MultipleAttributesFoundException extends RuntimeException
{
	public function __construct(string $className, ?Throwable $previous = null)
	{
		parent::__construct("Expected to only have one #[{$className}] attribute, but found more.", 0, $previous);
	}
}
