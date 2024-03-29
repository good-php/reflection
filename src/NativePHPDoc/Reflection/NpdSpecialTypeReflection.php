<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

/**
 * @template ReflectableType
 *
 * @implements SpecialTypeReflection<ReflectableType>
 */
final class NpdSpecialTypeReflection extends NpdTypeReflection implements SpecialTypeReflection
{
	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	public function __construct(
		private readonly SpecialTypeDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->type = new NamedType($this->qualifiedName(), $this->resolvedTypeParameterMap->toArguments($this->definition->typeParameters));
		$this->staticType = $this->type;
	}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($this->typeParameters);

		return $that;
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
			->map(fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->type));
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection
	{
		return $this->definition->superTypes;
	}
}
