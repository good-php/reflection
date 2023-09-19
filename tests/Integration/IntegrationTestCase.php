<?php

namespace Tests\Integration;

use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\ReflectorBuilder;

class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{
	protected Reflector $reflector;

	protected function setUp(): void
	{
		parent::setUp();

		$this->reflector = (new ReflectorBuilder())->build();
	}
}
