<?php

namespace Benchmark\Stubs;

use Attribute;

#[Attribute]
class AttributeStub
{
	public function __construct(public string $something) {}
}
