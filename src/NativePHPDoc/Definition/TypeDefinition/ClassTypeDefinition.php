<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template-covariant T of object
 */
final class ClassTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>               $qualifiedName
	 * @param list<TypeParameterDefinition> $typeParameters
	 * @param list<NamedType>               $implements
	 * @param list<TypeConstantDefinition>  $constants
	 * @param list<PropertyDefinition>      $properties
	 * @param list<MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly bool $anonymous,
		public readonly bool $final,
		public readonly bool $abstract,
		public readonly bool $readOnly,
		public readonly bool $cloneable,
		public readonly bool $instantiable,
		public readonly array $typeParameters,
		public readonly ?NamedType $extends,
		public readonly array $implements,
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
