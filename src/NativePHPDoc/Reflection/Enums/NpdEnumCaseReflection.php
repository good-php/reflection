<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Enums;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumCaseDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\Enums\EnumCaseReflection;
use ReflectionEnumUnitCase;
use UnitEnum;

/**
 * @template ReflectableType of UnitEnum
 * @template BackingValueType of string|int|null = string|int|null
 *
 * @implements EnumCaseReflection<ReflectableType, BackingValueType>
 */
final class NpdEnumCaseReflection implements EnumCaseReflection
{
	private readonly ReflectionEnumUnitCase $nativeReflection;

	private readonly Attributes $attributes;

	/**
	 * @param EnumReflection<ReflectableType, BackingValueType> $declaringEnum
	 */
	public function __construct(
		private readonly EnumCaseDefinition $definition,
		private readonly EnumReflection $declaringEnum,
	) {}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function backingValue(): string|int|null
	{
		/* @phpstan-ignore return.type */
		return $this->definition->backingValue;
	}

	public function value(): UnitEnum
	{
		/* @phpstan-ignore return.type */
		return $this->nativeReflection()->getValue();
	}

	public function declaringEnum(): EnumReflection
	{
		return $this->declaringEnum;
	}

	private function nativeReflection(): ReflectionEnumUnitCase
	{
		return $this->nativeReflection ??= new ReflectionEnumUnitCase(
			$this->declaringEnum->qualifiedName(),
			$this->definition->name,
		);
	}

	public function __toString(): string
	{
		return $this->name();
	}
}
