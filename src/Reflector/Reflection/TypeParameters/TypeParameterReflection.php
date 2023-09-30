<?php

namespace GoodPhp\Reflection\Reflector\Reflection\TypeParameters;

use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Stringable;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 */
final class TypeParameterReflection implements Stringable
{
	/**
	 * @param DeclaringStructureReflection $declaringStructure
	 */
	public function __construct(
		private readonly TypeParameterDefinition $definition,
		public readonly HasTypeParameters $declaringStructure,
		public NamedType $staticType,
	) {}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function variadic(): bool
	{
		return $this->definition->variadic;
	}

	public function upperBound(): Type
	{
		return TypeProjector::templateTypes(
			$this->definition->upperBound ?? MixedType::get(),
			TypeParameterMap::empty(),
			$this->staticType,
		);
	}

	public function variance(): TemplateTypeVariance
	{
		return $this->definition->variance;
	}

	public function __toString(): string
	{
		return (string) $this->definition;
	}
}
