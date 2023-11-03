<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template ReflectableType of object
 *
 * @implements ClassReflection<ReflectableType>
 */
final class NpdClassReflection extends NpdTypeReflection implements ClassReflection
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

	private readonly ?NamedType $extends;

	/** @var Collection<int, NamedType> */
	private readonly Collection $implements;

	private UsedTraitsReflection $uses;

	/** @var Collection<int, PropertyReflection<ReflectableType, $this>> */
	private readonly Collection $declaredProperties;

	/** @var Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>> */
	private readonly Collection $properties;

	/** @var Collection<int, MethodReflection<ReflectableType, $this>> */
	private readonly Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>> */
	private readonly Collection $methods;

	/**
	 * @param ClassTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly ClassTypeDefinition $definition,
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
		unset($this->typeParameters, $this->extends, $that->implements, $that->uses, $that->declaredProperties, $that->properties, $that->declaredMethods, $that->methods);

		return $that;
	}

	public function type(): NamedType
	{
		return $this->type;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
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

	public function extends(): ?NamedType
	{
		if (isset($this->extends)) {
			return $this->extends;
		}

		if (!$this->definition->extends) {
			return null;
		}

		return $this->extends ??= TypeProjector::templateTypes(
			$this->definition->extends,
			$this->resolvedTypeParameterMap,
			$this->staticType,
		);
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection
	{
		return $this->implements ??= $this->definition
			->implements
			->map(fn (NamedType $type) => TypeProjector::templateTypes(
				$type,
				$this->resolvedTypeParameterMap,
				$this->staticType,
			));
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
			...($this->extends() ? $this->propertiesFromTypes($this->extends(), $this->staticType, $this->reflector) : []),
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
			...$this->methodsFromTypes($this->implements(), $this->staticType, $this->reflector),
			...$this->methodsFromTraits($this->uses(), $this->staticType, $this->reflector),
			...($this->extends() ? $this->methodsFromTypes($this->extends(), $this->staticType, $this->reflector) : []),
			...$this->declaredMethods(),
		])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values();
	}

	/**
	 * @return MethodReflection<ReflectableType, HasMethods<ReflectableType>>|null
	 */
	public function constructor(): ?MethodReflection
	{
		return $this
			->methods()
			->first(fn (MethodReflection $reflection) => $reflection->name() === '__construct');
	}

	public function isAnonymous(): bool
	{
		return $this->definition->anonymous;
	}

	public function isAbstract(): bool
	{
		return $this->definition->abstract;
	}

	public function isFinal(): bool
	{
		return $this->definition->final;
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	/**
	 * @return ReflectableType
	 */
	public function newInstance(mixed ...$args): object
	{
		return $this->nativeReflection()->newInstance(...$args);
	}

	/**
	 * @return ReflectableType
	 */
	public function newInstanceWithoutConstructor(): object
	{
		return $this->nativeReflection()->newInstanceWithoutConstructor();
	}

	/**
	 * @return ReflectionClass<ReflectableType>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
