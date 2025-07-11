<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\Fallback;

use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

class FallbackDefinitionProvider implements DefinitionProvider
{
	/**
	 * @param list<DefinitionProvider> $providers
	 */
	public function __construct(
		private readonly array $providers
	) {}

	public function forType(string $type): ?TypeDefinition
	{
		return $this->fallback(
			$type,
			fn (DefinitionProvider $provider) => $provider->forType($type)
		);
	}

	/**
	 * @template ItemType
	 *
	 * @param callable(DefinitionProvider): ?ItemType $callback
	 *
	 * @return ItemType|null
	 */
	private function fallback(string $type, callable $callback): mixed
	{
		foreach ($this->providers as $provider) {
			$definition = $callback($provider);

			if ($definition) {
				return $definition;
			}
		}

		return null;
	}
}
