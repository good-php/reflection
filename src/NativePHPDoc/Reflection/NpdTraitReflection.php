<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NpdAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template-covariant T of object
 *
 * @extends NpdTypeReflection<T>
 *
 * @implements TraitReflection<T>
 */
final class NpdTraitReflection extends NpdTypeReflection implements TraitReflection
{
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<T> */
	private readonly ReflectionClass $nativeReflection;

	/** @var Collection<int, NpdTypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly NpdAttributes $attributes;

	private NpdUsedTraitsReflection $uses;

	/** @var Collection<int, NpdPropertyReflection<$this>> */
	private Collection $declaredProperties;

	/** @var Collection<int, NpdPropertyReflection<$this|self<object>>> */
	private Collection $properties;

	/** @var Collection<int, NpdMethodReflection<$this>> */
	private Collection $declaredMethods;

	/** @var Collection<int, NpdMethodReflection<$this|self<object>>> */
	private Collection $methods;

	/**
	 * @param TraitTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly TraitTypeDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
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
		unset($this->typeParameters, $that->uses, $that->declaredProperties, $that->properties, $that->declaredMethods, $that->methods);

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

	public function attributes(): NpdAttributes
	{
		return $this->attributes ??= new NpdAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return Collection<int, NpdTypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection
	{
		return $this->typeParameters ??= $this->definition
			->typeParameters
			->map(fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->staticType));
	}

	public function uses(): NpdUsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, $this->resolvedTypeParameterMap, $this->staticType);
	}

	/**
	 * @return Collection<int, NpdPropertyReflection<$this>>
	 */
	public function declaredProperties(): Collection
	{
		return $this->declaredProperties ??= $this->definition
			->properties
			->map(fn (PropertyDefinition $property) => new NpdPropertyReflection($property, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, NpdPropertyReflection<$this|self<object>>>
	 */
	public function properties(): Collection
	{
		if (isset($this->properties)) {
			return $this->properties;
		}

		return collect([
			...$this->propertiesFromTraits($this->uses(), $this->staticType, $this->reflector),
			...$this->declaredProperties(),
		])
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values();
	}

	/**
	 * @return Collection<int, NpdMethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, NpdMethodReflection<$this|self<object>>>
	 */
	public function methods(): Collection
	{
		if (isset($this->methods)) {
			return $this->methods;
		}

		return collect([
			...$this->methodsFromTraits($this->uses(), $this->staticType, $this->reflector),
			...$this->declaredMethods(),
		])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	/**
	 * @return ReflectionClass<T>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
