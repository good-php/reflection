<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumCaseDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeConstantDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Constants\NpdTypeConstantReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Enums\NpdEnumCaseReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassMemberInheritanceResolver;
use GoodPhp\Reflection\Reflection\Constants\HasConstantsDefaults;
use GoodPhp\Reflection\Reflection\Constants\TypeConstantReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\Enums\EnumCaseReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethodsDefaults;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Arr;
use ReflectionEnum;

/**
 * @template ReflectableType of \UnitEnum
 * @template BackingValueType of string|int|null = string|int|null
 *
 * @implements EnumReflection<ReflectableType, BackingValueType>
 */
final class NpdEnumReflection extends NpdTypeReflection implements EnumReflection
{
	/** @use HasConstantsDefaults<ReflectableType> */
	use HasConstantsDefaults;

	/** @use HasMethodsDefaults<ReflectableType> */
	use HasMethodsDefaults;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionEnum<ReflectableType> */
	private readonly ReflectionEnum $nativeReflection;

	private readonly Attributes $attributes;

	private UsedTraitsReflection $uses;

	/** @var list<EnumCaseReflection<ReflectableType, BackingValueType>> */
	private array $cases;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $declaredConstants;

	/** @var list<TypeConstantReflection<ReflectableType>> */
	private array $constants;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType>> */
	private array $methods;

	/**
	 * @param EnumTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly EnumTypeDefinition $definition,
		private readonly Reflector $reflector,
		private readonly ClassMemberInheritanceResolver $classMemberInheritanceResolver,
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

	public function cases(): array
	{
		return $this->cases ??= array_map(
			fn (EnumCaseDefinition $case) => new NpdEnumCaseReflection($case, $this),
			$this->definition->cases,
		);
	}

	public function case(string $name): ?EnumCaseReflection
	{
		return Arr::first($this->cases(), fn (EnumCaseReflection $method) => $name === $method->name());
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
			fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, TypeParameterMap::empty()),
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
			implements: $this->implements(),
			usedTraits: $this->uses(),
		);
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
