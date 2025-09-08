<?php

namespace GoodPhp\Reflection\Cache;

use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\CacheUtils;
use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\TypeComparator;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

class StaticCacheReflector implements Reflector
{
	public function __construct(
		private readonly Reflector $delegate,
		private readonly CacheInterface $cache,
	) {}

	public function typeComparator(): TypeComparator
	{
		return $this->delegate->typeComparator();
	}

	public function forNamedType(NamedType $type): TypeReflection
	{
		$key = 'reflection.' . CacheUtils::normalizeTypeName((string) $type);

		if ($reflection = $this->cache->get($key)) {
			Assert::isInstanceOf($reflection, TypeReflection::class);

			return $reflection;
		}

		$reflection = $this->delegate->forNamedType($type);

		$this->cache->set($key, $reflection);

		return $reflection;
	}

	public function forType(string $name, array $arguments = []): TypeReflection
	{
		return $this->forNamedType(new NamedType($name, $arguments));
	}
}
