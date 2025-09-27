<?php

namespace Tests\Stubs\Classes;

use Illuminate\Support\Collection;
use Tests\Stubs\AttributeStub;

/**
 * Parent class description
 *
 * @template O
 * @template U
 */
class ParentClassStub
{
	/**
	 * Parent property description
	 *
	 * @var O
	 */
	private mixed $parentProperty = null;

	/**
	 * Parent method description
	 *
	 * @return U
	 */
	public function parentMethod(): mixed {}

	public function test(?string $str = null): static {}
}
