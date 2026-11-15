<?php

namespace Tests\Stubs;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AttributeStub
{
	public function __construct(public string $something) {}
}
