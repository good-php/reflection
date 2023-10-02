<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use Illuminate\Support\Collection;

/**
 * @template-covariant T of object
 */
final class TraitTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>                          $qualifiedName
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 * @param Collection<int, PropertyDefinition>      $properties
	 * @param Collection<int, MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly Collection $typeParameters,
		public readonly UsedTraitsDefinition $uses,
		public readonly Collection $properties,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
