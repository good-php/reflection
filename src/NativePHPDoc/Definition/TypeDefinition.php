<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition;

abstract class TypeDefinition
{
	public function __construct(
		public readonly string $qualifiedName,
		public readonly ?string $fileName,
	) {}
}
