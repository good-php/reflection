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
use GoodPhp\Reflection\Reflection\ClassMemberInheritanceResolver;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethodsDefaults;
use GoodPhp\Reflection\Reflection\Properties\HasPropertiesDefaults;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParametersDefaults;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionClass;

/**
 * @template ReflectableType of object
 *
 * @implements ClassReflection<ReflectableType>
 */
final class NpdClassReflection extends NpdTypeReflection implements ClassReflection
{
	/** @use HasMethodsDefaults<ReflectableType> */
	use HasMethodsDefaults;

	/** @use HasPropertiesDefaults<ReflectableType> */
	use HasPropertiesDefaults;

	use HasTypeParametersDefaults;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<ReflectableType> */
	private readonly ReflectionClass $nativeReflection;

	/** @var list<TypeParameterReflection> */
	private array $typeParameters;

	private readonly Attributes $attributes;

	private ?NamedType $extends;

	/** @var list<NamedType> */
	private array $implements;

	private UsedTraitsReflection $uses;

	/** @var list<PropertyReflection<ReflectableType>> */
	private array $declaredProperties;

	/** @var list<PropertyReflection<ReflectableType>> */
	private array $properties;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $methods;

	/**
	 * @param ClassTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly ClassTypeDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
		private readonly ClassMemberInheritanceResolver $classMemberInheritanceResolver,
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
	 * @return list<TypeParameterReflection>
	 */
	public function typeParameters(): array
	{
		return $this->typeParameters ??= array_map(
			fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->staticType),
			$this->definition->typeParameters
		);
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
	 * @return list<NamedType>
	 */
	public function implements(): array
	{
		return $this->implements ??= array_map(
			fn (NamedType $type) => TypeProjector::templateTypes(
				$type,
				$this->resolvedTypeParameterMap,
				$this->staticType,
			),
			$this->definition->implements
		);
	}

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, $this->resolvedTypeParameterMap, $this->staticType);
	}

	/**
	 * @return list<PropertyReflection<ReflectableType>>
	 */
	public function declaredProperties(): array
	{
		return $this->declaredProperties ??= array_map(
			fn (PropertyDefinition $property) => new NpdPropertyReflection($property, $this, $this->staticType, $this->resolvedTypeParameterMap),
			$this->definition->properties,
		);
	}

	/**
	 * @return list<PropertyReflection<ReflectableType>>
	 */
	public function properties(): array
	{
		return $this->properties ??= $this->classMemberInheritanceResolver->properties(
			reflector: $this->reflector,
			staticType: $this->staticType,
			declaredProperties: $this->declaredProperties(),
			extends: $this->extends(),
			implements: $this->implements(),
			usedTraits: $this->uses(),
		);
	}

	/**
	 * @return list<MethodReflection<ReflectableType>>
	 */
	public function declaredMethods(): array
	{
		return $this->declaredMethods ??= array_map(
			fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap),
			$this->definition->methods,
		);
	}

	/**
	 * @return list<MethodReflection<ReflectableType>>
	 */
	public function methods(): array
	{
		return $this->methods ??= $this->classMemberInheritanceResolver->methods(
			reflector: $this->reflector,
			staticType: $this->staticType,
			declaredMethods: $this->declaredMethods(),
			extends: $this->extends(),
			implements: $this->implements(),
			usedTraits: $this->uses(),
		);
	}

	/**
	 * @return MethodReflection<ReflectableType>|null
	 */
	public function constructor(): ?MethodReflection
	{
		return $this->method('__construct');
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

	public function isReadOnly(): bool
	{
		return $this->definition->readOnly;
	}

	public function isCloneable(): bool
	{
		return $this->definition->cloneable;
	}

	public function isInstantiable(): bool
	{
		return $this->definition->instantiable;
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
