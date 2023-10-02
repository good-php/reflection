<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NpdAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;
use ReflectionEnum;

/**
 * @template-covariant T of \UnitEnum
 *
 * @extends NpdTypeReflection<T>
 *
 * @implements EnumReflection<T>
 */
final class NpdEnumReflection extends NpdTypeReflection implements EnumReflection
{
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	private readonly ReflectionEnum $nativeReflection;

	private readonly Attributes $attributes;

	private UsedTraitsReflection $uses;

	/** @var Collection<int, MethodReflection<$this>> */
	private readonly Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<HasMethods>> */
	private readonly Collection $methods;

	/**
	 * @param EnumTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly EnumTypeDefinition $definition,
		private readonly Reflector $reflector
	) {
		$this->type = new NamedType($this->qualifiedName());
		$this->staticType = $this->type;
	}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($that->declaredMethods, $that->methods);

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
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection
	{
		return $this->definition->implements;
	}

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, TypeParameterMap::empty(), $this->staticType);
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, TypeParameterMap::empty()));
	}

	/**
	 * @return Collection<int, MethodReflection<HasMethods>>
	 */
	public function methods(): Collection
	{
		return $this->methods ??= collect([
			...$this->methodsFromTypes($this->implements(), $this->staticType, $this->reflector),
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

	private function nativeReflection(): ReflectionEnum
	{
		return $this->nativeReflection ??= new ReflectionEnum($this->definition->qualifiedName);
	}
}
