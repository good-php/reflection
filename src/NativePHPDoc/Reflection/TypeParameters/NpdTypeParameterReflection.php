<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;

/**
 * @template-covariant DeclaringStructureReflection of HasTypeParameters
 *
 * @implements TypeParameterReflection<DeclaringStructureReflection>
 */
final class NpdTypeParameterReflection implements TypeParameterReflection
{
	public function __construct(
		private readonly TypeParameterDefinition $definition,
		private readonly HasTypeParameters $declaringStructure,
		private NamedType $staticType,
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

	/**
	 * @return DeclaringStructureReflection
	 */
	public function declaringStructure(): HasTypeParameters
	{
		return $this->declaringStructure;
	}

	public function __toString(): string
	{
		return (string) $this->definition;
	}
}
