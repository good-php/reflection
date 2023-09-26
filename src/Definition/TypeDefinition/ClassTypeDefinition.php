<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template-covariant T of object
 */
final class ClassTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>                          $qualifiedName
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 * @param Collection<int, NamedType>               $implements
	 * @param Collection<int, NamedType>               $uses
	 * @param Collection<int, PropertyDefinition>      $properties
	 * @param Collection<int, MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly bool $anonymous,
		public readonly bool $final,
		public readonly bool $abstract,
		public readonly Collection $typeParameters,
		public readonly ?NamedType $extends,
		public readonly Collection $implements,
		public readonly Collection $uses,
		public readonly Collection $properties,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
