<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition;

class LazyDefinitionProvider implements DefinitionProvider
{
	private readonly DefinitionProvider $provider;

	/**
	 * @param callable(): DefinitionProvider $resolve
	 */
	public function __construct(
		private readonly mixed $resolve,
	) {}

	public function forType(string $type): ?TypeDefinition
	{
		return $this->provider()->forType($type);
	}

	private function provider(): DefinitionProvider
	{
		return $this->provider ??= ($this->resolve)();
	}
}
