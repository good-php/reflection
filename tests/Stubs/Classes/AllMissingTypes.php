<?php

namespace Tests\Stubs\Classes;

use Tests\Stubs\Interfaces\SingleTemplateType;

class AllMissingTypes extends SomeStub implements SingleTemplateType
{
	public $property = 123;

	public function __construct(
		public $promoted = true,
	) {
	}

	public function test($something)
	{
	}
}
