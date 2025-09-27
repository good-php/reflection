<?php

namespace Tests\Stubs;

use Attribute;

#[Attribute]
readonly class AttributeStub
{
	public function __construct(public string $something) {}
}
