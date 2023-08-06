<?php

namespace GoodPhp\Reflection\Reflector;

use RuntimeException;
use Throwable;

class UnknownTypeException extends RuntimeException
{
	public function __construct(string $type, Throwable $previous = null)
	{
		parent::__construct("Unable to reflect type '{$type}'.", 0, $previous);
	}
}
