<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeConstantDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Constants\NpdTypeConstantReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassMemberInheritanceResolver;
use GoodPhp\Reflection\Reflection\Constants\HasConstantsDefaults;
use GoodPhp\Reflection\Reflection\Constants\TypeConstantReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethodsDefaults;
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
 * @implements InterfaceReflection<ReflectableType>
 */
final class NpdInterfaceReflection extends NpdTypeReflection implements InterfaceReflection
{
	/** @use HasConstantsDefaults<ReflectableType> */
	use HasConstantsDefaults;

	/** @use HasMethodsDefaults<ReflectableType> */
	use HasMethodsDefaults;

	use HasTypeParametersDefaults;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var list<TypeParameterReflection> */
	private array $typeParameters;

	/** @var ReflectionClass<ReflectableType> */
	private readonly ReflectionClass $nativeReflection;

	private readonly Attributes $attributes;

	/** @var list<NamedType> */
	private array $extends;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $declaredConstants;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $constants;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $methods;

	/**
	 * @param InterfaceTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly InterfaceTypeDefinition $definition,
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
		unset($this->typeParameters, $this->extends, $that->declaredMethods, $that->methods);

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

	/**
	 * @return list<NamedType>
	 */
	public function extends(): array
	{
		return $this->extends ??= array_map(fn (NamedType $type) => TypeProjector::templateTypes(
			$type,
			$this->resolvedTypeParameterMap,
			$this->staticType,
		), $this->definition->extends);
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
			implements: $this->extends(),
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
			implements: $this->extends(),
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
