<?php

namespace Tests\Integration;

use GoodPhp\Reflection\ReflectionBuilder;
use Psr\Container\ContainerInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
	protected ContainerInterface $container;

	protected function setUp(): void
	{
		parent::setUp();

		$this->container = (new ReflectionBuilder())->build();
	}
}
