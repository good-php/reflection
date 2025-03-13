<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\Type;

final class SpecialTypeDefinition extends TypeDefinition
{
	/**
	 * @param list<TypeParameterDefinition> $typeParameters
	 * @param list<Type>                    $superTypes
	 */
	public function __construct(
		string $qualifiedName,
		public readonly array $typeParameters = [],
		public readonly array $superTypes = [],
	) {
		parent::__construct(
			$qualifiedName,
			null,
		);
	}
}
