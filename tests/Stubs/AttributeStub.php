<?php

namespace Tests\Stubs;

use Attribute;

#[Attribute]
class AttributeStub
{
	public function __construct(public string $something)
	{
	}
}
