<?php

namespace GoodPhp\Reflection\Util;

trait IsSingleton
{
	private static self $instance;

	public static function get(): static
	{
		return self::$instance ??= new static();
	}
}
