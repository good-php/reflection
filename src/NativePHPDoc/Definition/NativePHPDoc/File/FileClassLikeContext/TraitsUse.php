<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;

final class TraitsUse
{
	/**
	 * @param list<TraitUse> $traits
	 */
	public function __construct(
		public readonly array $traits,
		public readonly ?string $docComment = null,
	) {}
}
