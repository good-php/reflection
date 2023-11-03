<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template ReflectableType of object
 *
 * @implements TraitReflection<ReflectableType>
 */
final class NpdTraitReflection extends NpdTypeReflection implements TraitReflection
{
	/** @use InheritsClassMembers<ReflectableType> */
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<ReflectableType> */
	private readonly ReflectionClass $nativeReflection;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly Attributes $attributes;

	private UsedTraitsReflection $uses;

	/** @var Collection<int, PropertyReflection<ReflectableType, $this>> */
	private Collection $declaredProperties;

	/** @var Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>> */
	private Collection $properties;

	/** @var Collection<int, MethodReflection<ReflectableType, $this>> */
	private Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>> */
	private Collection $methods;

	/**
	 * @param TraitTypeDefinition<ReflectableType> $definition
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

	public function attributes(): Attributes
	{
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return Collection<int, TypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection
	{
		return $this->typeParameters ??= $this->definition
			->typeParameters
			->map(fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->staticType));
	}

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, $this->resolvedTypeParameterMap, $this->staticType);
	}

	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, $this>>
	 */
	public function declaredProperties(): Collection
	{
		return $this->declaredProperties ??= $this->definition
			->properties
			->map(fn (PropertyDefinition $property) => new NpdPropertyReflection($property, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	public function properties(): Collection
	{
		return $this->properties ??= collect([
			...$this->propertiesFromTraits($this->uses(), $this->staticType, $this->reflector),
			...$this->declaredProperties(),
		])
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values();
	}

	/**
	 * @return Collection<int, MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	public function methods(): Collection
	{
		return $this->methods ??= collect([
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
	 * @return ReflectionClass<ReflectableType>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
