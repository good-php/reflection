<?php

namespace GoodPhp\Reflection\Definition;

interface DefinitionProvider
{
	/**
	 * @param class-string $type
	 */
	public function forType(string $type): ?TypeDefinition;
}
