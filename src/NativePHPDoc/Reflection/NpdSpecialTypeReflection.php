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

/**
 * @template ReflectableType
 *
 * @implements SpecialTypeReflection<ReflectableType>
 */
final class NpdSpecialTypeReflection extends NpdTypeReflection implements SpecialTypeReflection
{
	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var list<TypeParameterReflection<$this>> */
	private readonly array $typeParameters;

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
	 * @return list<TypeParameterReflection<$this>>
	 */
	public function typeParameters(): array
	{
		return $this->typeParameters ??= array_map(
			fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->type),
			$this->definition->typeParameters
		);
	}

	/**
	 * @return list<Type>
	 */
	public function superTypes(): array
	{
		return $this->definition->superTypes;
	}
}
