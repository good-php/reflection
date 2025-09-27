<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

/**
 * @template-covariant T of object
 */
final class TraitTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>               $qualifiedName
	 * @param list<TypeParameterDefinition> $typeParameters
	 * @param list<TypeConstantDefinition>  $constants
	 * @param list<PropertyDefinition>      $properties
	 * @param list<MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly ?string $description,
		public readonly bool $builtIn,
		public readonly array $typeParameters,
		public readonly UsedTraitsDefinition $uses,
		public readonly array $constants,
		public readonly array $properties,
		public readonly array $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
