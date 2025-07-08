<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
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
use ReflectionEnum;

/**
 * @template ReflectableType of \UnitEnum
 *
 * @implements EnumReflection<ReflectableType>
 */
final class NpdEnumReflection extends NpdTypeReflection implements EnumReflection
{
	/** @use InheritsClassMembers<ReflectableType> */
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionEnum<ReflectableType> */
	private readonly ReflectionEnum $nativeReflection;

	private readonly Attributes $attributes;

	private UsedTraitsReflection $uses;

	/** @var list<MethodReflection<ReflectableType, $this>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>> */
	private array $methods;

	/**
	 * @param EnumTypeDefinition<ReflectableType> $definition
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
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return list<NamedType>
	 */
	public function implements(): array
	{
		return $this->definition->implements;
	}

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, TypeParameterMap::empty(), $this->staticType);
	}

	/**
	 * @return list<MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): array
	{
		return $this->declaredMethods ??= array_map(
			fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, TypeParameterMap::empty()),
			$this->definition->methods,
		);
	}

	/**
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	public function methods(): array
	{
		return $this->methods ??= collect([
			...$this->methodsFromTypes($this->implements(), $this->staticType, $this->reflector),
			...$this->methodsFromTraits($this->uses(), $this->staticType, $this->reflector),
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
	 * @return ReflectionEnum<ReflectableType>
	 */
	private function nativeReflection(): ReflectionEnum
	{
		return $this->nativeReflection ??= new ReflectionEnum($this->definition->qualifiedName);
	}
}
