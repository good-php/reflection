<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;

use Illuminate\Support\Collection;
use ReflectionMethod;

final class TraitUse
{
	/**
	 * @param Collection<int, array{ string, string|null, int-mask-of<ReflectionMethod::IS_*>|null }> $aliases [old method name, new method name, new visibility]
	 */
	public function __construct(
		public readonly string $qualifiedName,
		public readonly Collection $aliases = new Collection(),
	) {}
}
