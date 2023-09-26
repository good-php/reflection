<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

/**
 * @template-covariant T
 *
 * @extends TypeReflection<T>
 */
final class SpecialTypeReflection extends TypeReflection implements HasTypeParameters
{
	public function __construct(
		private readonly SpecialTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection
	{
		return $this->definition->superTypes;
	}
}
