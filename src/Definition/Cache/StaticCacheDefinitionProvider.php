<?php

namespace GoodPhp\Reflection\Definition\Cache;

use GoodPhp\Reflection\Definition\DefinitionProvider;
use GoodPhp\Reflection\Definition\TypeDefinition;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

class StaticCacheDefinitionProvider implements DefinitionProvider
{
	public function __construct(
		private readonly DefinitionProvider $delegate,
		private readonly CacheInterface $cache
	) {}

	public function forType(string $type): ?TypeDefinition
	{
		$key = 'type.' . str_replace('\\', '.', $type);

		if ($definition = $this->cache->get($key)) {
			Assert::isInstanceOf($definition, TypeDefinition::class);

			return $definition;
		}

		$definition = $this->delegate->forType($type);

		$this->cache->set($key, $definition);

		return $definition;
	}
}
