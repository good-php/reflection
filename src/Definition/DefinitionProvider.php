<?php

namespace GoodPhp\Reflection\Definition;

interface DefinitionProvider
{
	/**
	 * @param class-string|string $type
	 */
	public function forType(string $type): ?TypeDefinition;
}
