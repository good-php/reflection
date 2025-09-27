<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template-covariant T of object
 */
final class InterfaceTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>               $qualifiedName
	 * @param list<TypeParameterDefinition> $typeParameters
	 * @param list<NamedType>               $extends
	 * @param list<TypeConstantDefinition>  $constants
	 * @param list<MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly ?string $description,
		public readonly bool $builtIn,
		public readonly array $typeParameters,
		public readonly array $extends,
		public readonly array $constants,
		public readonly array $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
