<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
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
	/** @use HasMethodsDefaults<ReflectableType> */
	use HasMethodsDefaults;

	use HasTypeParametersDefaults;

	/** @use InheritsClassMembers<ReflectableType> */
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var list<TypeParameterReflection> */
	private array $typeParameters;

	/** @var ReflectionClass<ReflectableType> */
	private readonly ReflectionClass $nativeReflection;

	private readonly Attributes $attributes;

	/** @var list<NamedType> */
	private array $extends;

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
		return $this->methods ??= collect([
			...$this->methodsFromTypes($this->extends(), $this->staticType, $this->reflector),
			...$this->declaredMethods(),
		])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->all();
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
