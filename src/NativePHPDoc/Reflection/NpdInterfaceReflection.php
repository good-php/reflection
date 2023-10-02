<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NpdAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template-covariant T of object
 *
 * @extends NpdTypeReflection<T>
 *
 * @implements InterfaceReflection<T>
 */
final class NpdInterfaceReflection extends NpdTypeReflection implements InterfaceReflection
{
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	/** @var ReflectionClass<T> */
	private readonly ReflectionClass $nativeReflection;

	private readonly Attributes $attributes;

	/** @var Collection<int, NamedType> */
	private readonly Collection $extends;

	/** @var Collection<int, MethodReflection<$this>> */
	private readonly Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<HasMethods>> */
	private readonly Collection $methods;

	/**
	 * @param InterfaceTypeDefinition<T> $definition
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
		return $this->attributes ??= new NpdAttributes(
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

	/**
	 * @return Collection<int, NamedType>
	 */
	public function extends(): Collection
	{
		return $this->extends ??= $this->definition
			->extends
			->map(fn (NamedType $type) => TypeProjector::templateTypes(
				$type,
				$this->resolvedTypeParameterMap,
				$this->staticType,
			));
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, MethodReflection<HasMethods>>
	 */
	public function methods(): Collection
	{
		return $this->methods ??= collect([
			...$this->methodsFromTypes($this->extends(), $this->staticType, $this->reflector),
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
