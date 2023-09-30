<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use GoodPhp\Reflection\Definition\NativePHPDoc\File\FileClassLikeContext\TraitUse;
use Illuminate\Support\Collection;

class FileClassLikeContext
{
	public function __construct(
		public readonly ?string $namespace,
		/** @var Collection<int, string> */
		public readonly Collection $implementsInterfaces,
		/** @var Collection<string, string> */
		public readonly Collection $uses,
		/** @var Collection<int, TraitUse> */
		public readonly Collection $traitUses,
		/** @var Collection<int, string> */
		public readonly Collection $declaredProperties,
		/** @var Collection<int, string> */
		public readonly Collection $declaredMethods,
	) {}
}
