<?php

namespace GoodPhp\Reflection\Util;

trait IsSingleton
{
	/** @var static */
	private static self $instance;

	public static function get(): static
	{
		return self::$instance ??= new static();
	}
}
