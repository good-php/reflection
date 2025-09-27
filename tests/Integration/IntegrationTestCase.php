<?php

namespace Tests\Integration;

use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\ReflectorBuilder;
use Tests\TestCase;

class IntegrationTestCase extends TestCase
{
	protected Reflector $reflector;

	protected function setUp(): void
	{
		parent::setUp();

		$this->reflector = (new ReflectorBuilder())
			->withMemoryCache()
			->build();
	}
}
