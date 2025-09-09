<?php

namespace Benchmark\Stubs\Classes;

use Benchmark\Stubs\Interfaces\SingleTemplateType;

class AllMissingTypes extends SomeStub implements SingleTemplateType
{
	public $property = 123;

	public function __construct(
		public $promoted = true,
		public readonly ?int $promotedDefault = null,
	) {}

	public function test($something) {}
}
