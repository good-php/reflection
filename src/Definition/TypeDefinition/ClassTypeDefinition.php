<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

final class ClassTypeDefinition extends TypeDefinition
{
	/**
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 * @param Collection<int, Type>                    $implements
	 * @param Collection<int, Type>                    $uses
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
		public readonly ?Type $extends,
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
