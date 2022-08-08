<?php

namespace GoodPhp\Reflection\Definition;

abstract class TypeDefinition
{
	public function __construct(
		public readonly string $qualifiedName,
		public readonly ?string $fileName,
	) {
	}
}
