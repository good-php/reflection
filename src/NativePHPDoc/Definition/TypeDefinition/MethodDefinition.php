<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\Type;

final class MethodDefinition
{
	/**
	 * @param list<TypeParameterDefinition>     $typeParameters
	 * @param list<FunctionParameterDefinition> $parameters
	 */
	public function __construct(
		public readonly string $name,
		public readonly bool $abstract,
		public readonly bool $final,
		public readonly array $typeParameters,
		public readonly array $parameters,
		public readonly ?Type $returnType,
		public readonly ?TypeSource $returnTypeSource,
		public readonly bool $returnsByReference,
	) {}
}
