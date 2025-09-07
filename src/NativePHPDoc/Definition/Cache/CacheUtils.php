<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\Cache;

class CacheUtils
{
	public static function normalizeTypeName(string $type): string
	{
		return str_replace(['\\', '{', '}', '(', ')', '/', '@', ':'], '.', $type);
	}
}
