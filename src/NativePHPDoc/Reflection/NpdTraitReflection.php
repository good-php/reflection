<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeConstantDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Constants\NpdTypeConstantReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassMemberInheritanceResolver;
use GoodPhp\Reflection\Reflection\Constants\HasConstantsDefaults;
use GoodPhp\Reflection\Reflection\Constants\TypeConstantReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethodsDefaults;
use GoodPhp\Reflection\Reflection\Properties\HasPropertiesDefaults;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParametersDefaults;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use ReflectionClass;

/**
 * @template ReflectableType of object
 *
 * @implements TraitReflection<ReflectableType>
 */
final class NpdTraitReflection extends NpdTypeReflection implements TraitReflection
{
	/** @use HasConstantsDefaults<ReflectableType> */
	use HasConstantsDefaults;

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

	private UsedTraitsReflection $uses;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $declaredConstants;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $constants;

	/** @var list<PropertyReflection<ReflectableType>> */
	private array $declaredProperties;

	/** @var list<PropertyReflection<ReflectableType>> */
	private array $properties;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $methods;

	/**
	 * @param TraitTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly TraitTypeDefinition $definition,
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

	public function description(): ?string
	{
		return $this->definition->description;
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

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, $this->resolvedTypeParameterMap, $this->staticType);
	}

	/**
	 * @return list<TypeConstantReflection<ReflectableType>>
	 */
	public function declaredConstants(): array
	{
		return $this->declaredConstants ??= array_map(
			fn (TypeConstantDefinition $constant) => new NpdTypeConstantReflection($constant, $this, $this->staticType),
			$this->definition->constants,
		);
	}

	/**
	 * @return list<TypeConstantReflection<ReflectableType>>
	 */
	public function constants(): array
	{
		return $this->constants ??= $this->classMemberInheritanceResolver->constants(
			reflector: $this->reflector,
			staticType: $this->staticType,
			declaredConstants: $this->declaredConstants(),
			usedTraits: $this->uses(),
		);
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
			$this->definition->methods
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
			usedTraits: $this->uses(),
		);
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
