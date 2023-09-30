<?php

namespace Tests\Stubs\Interfaces;

/**
 * @template O
 * @template U
 */
interface ParentInterfaceStub
{
	public function test(string $str): static;
}
