<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;

use Illuminate\Support\Collection;

final class TraitsUse
{
	/**
	 * @param Collection<int, TraitUse> $traits
	 */
	public function __construct(
		public readonly Collection $traits,
		public readonly ?string $docComment = null,
	) {}
}
