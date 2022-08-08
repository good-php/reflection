<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use Illuminate\Support\Collection;

class FileClassLikeContext
{
	public function __construct(
		public readonly ?string $namespace,
		public readonly Collection $uses,
		public readonly Collection $traitUses,
		public readonly Collection $declaredProperties,
		public readonly Collection $declaredMethods,
	) {
	}
}
