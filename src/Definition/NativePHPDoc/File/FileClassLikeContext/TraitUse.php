<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File\FileClassLikeContext;

class TraitUse
{
	/**
	 * @param array<string, string> $aliases
	 */
	public function __construct(
		public readonly string $name,
		public readonly ?string $docComment,
		public readonly array $aliases,
	) {}
}
