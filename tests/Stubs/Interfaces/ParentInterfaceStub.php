<?php

namespace Tests\Stubs\Interfaces;

/**
 * Parent interface description
 *
 * @template O
 * @template U
 */
interface ParentInterfaceStub
{
	public function test(string $str): static;
}
