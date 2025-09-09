<?php

namespace Benchmark\Stubs\Classes;

/**
 * @template O
 * @template U
 */
class ParentClassStub
{
	/** @var O */
	private mixed $parentProperty = null;

	/**
	 * @return U
	 */
	public function parentMethod(): mixed {}

	public function test(?string $str = null): static {}
}
