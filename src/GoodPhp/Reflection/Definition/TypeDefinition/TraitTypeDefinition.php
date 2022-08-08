<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

final class TraitTypeDefinition extends TypeDefinition
{
	/**
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 * @param Collection<int, Type>                    $uses
	 * @param Collection<int, PropertyDefinition>      $properties
	 * @param Collection<int, MethodDefinition>        $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly Collection $typeParameters,
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
