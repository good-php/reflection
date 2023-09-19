<?php

namespace Tests\Stubs\Classes;

use Illuminate\Support\Collection;
use Tests\Stubs\AttributeStub;

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
}
