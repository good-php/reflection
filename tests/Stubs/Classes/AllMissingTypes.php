<?php

namespace Tests\Stubs\Classes;

use Tests\Stubs\Interfaces\SingleTemplateType;

class AllMissingTypes extends SomeStub implements SingleTemplateType
{
	public $property;

	public function test($something)
	{
	}
}
