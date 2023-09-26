<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template-covariant T of object
 */
final class InterfaceTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>                          $qualifiedName
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 * @param Collection<int, NamedType>               $extends
	 * @param Collection<int, MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly Collection $typeParameters,
		public readonly Collection $extends,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
