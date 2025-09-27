<?php

namespace Tests\Stubs\Interfaces;

interface NonGenericInterface
{
	final public const INTERFACE_CONSTANT = 'string';

	public function function(string $i): mixed;
}
