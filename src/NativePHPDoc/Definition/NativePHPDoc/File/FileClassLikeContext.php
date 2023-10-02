<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;
use Illuminate\Support\Collection;

class FileClassLikeContext
{
	/**
	 * @param Collection<int, string>                           $implementsInterfaces
	 * @param Collection<string, string>                        $uses
	 * @param Collection<int, TraitsUse>                        $traitsUses
	 * @param Collection<class-string, Collection<int, string>> $excludedTraitMethods
	 * @param Collection<int, string>                           $declaredProperties
	 * @param Collection<int, string>                           $declaredMethods
	 */
	public function __construct(
		public readonly ?string $namespace,
		public readonly Collection $implementsInterfaces,
		public readonly Collection $uses,
		public readonly Collection $traitsUses,
		public readonly Collection $excludedTraitMethods,
		public readonly Collection $declaredProperties,
		public readonly Collection $declaredMethods,
	) {}
}
