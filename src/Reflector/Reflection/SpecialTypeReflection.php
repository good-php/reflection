<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
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
	private readonly NamedType $type;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	public function __construct(
		private readonly SpecialTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->type = new NamedType($this->qualifiedName(), $this->resolvedTypeParameterMap->toArguments($this->definition->typeParameters));
	}

	public function type(): NamedType
	{
		return $this->type;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	/**
	 * @return Collection<int, TypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection
	{
		return $this->typeParameters ??= $this->definition
			->typeParameters
			->map(fn (TypeParameterDefinition $parameter) => new TypeParameterReflection($parameter, $this, $this->type));
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection
	{
		return $this->definition->superTypes;
	}
}
