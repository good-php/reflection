<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;

class FileClassLikeContext
{
	/**
	 * @param list<string>                      $implementsInterfaces
	 * @param array<string, string>             $uses
	 * @param list<TraitsUse>                   $traitsUses
	 * @param array<class-string, list<string>> $excludedTraitMethods
	 * @param list<string>                      $declaredProperties
	 * @param list<string>                      $declaredMethods
	 */
	public function __construct(
		public readonly ?string $namespace,
		public readonly array $implementsInterfaces,
		public readonly array $uses,
		public readonly array $traitsUses,
		public readonly array $excludedTraitMethods,
		public readonly array $declaredProperties,
		public readonly array $declaredMethods,
	) {}
}
