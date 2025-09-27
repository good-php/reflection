<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Constants;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeConstantDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\Constants\HasConstants;
use GoodPhp\Reflection\Reflection\Constants\TypeConstantReflection;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionClassConstant;

/**
 * @template-contravariant ReflectableType of object
 *
 * @implements TypeConstantReflection<ReflectableType>
 */
final class NpdTypeConstantReflection implements TypeConstantReflection
{
	private readonly ReflectionClassConstant $nativeReflection;

	private readonly Attributes $attributes;

	private Type $type;

	/**
	 * @param HasConstants<ReflectableType> $declaringType
	 */
	public function __construct(
		private readonly TypeConstantDefinition $definition,
		private readonly HasConstants $declaringType,
		private NamedType $staticType,
	) {}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($this->type);

		return $that;
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function isFinal(): bool
	{
		return $this->definition->isFinal;
	}

	public function type(): ?Type
	{
		if (isset($this->type)) {
			return $this->type;
		}

		if (!$this->definition->type) {
			return null;
		}

		return $this->type ??= TypeProjector::templateTypes(
			$this->definition->type,
			TypeParameterMap::empty(),
			$this->staticType,
		);
	}

	public function typeSource(): ?TypeSource
	{
		return $this->definition->typeSource;
	}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	public function value(): mixed
	{
		return $this->nativeReflection()->getValue();
	}

	public function declaringType(): HasConstants
	{
		return $this->declaringType;
	}

	private function nativeReflection(): ReflectionClassConstant
	{
		return $this->nativeReflection ??= new ReflectionClassConstant($this->declaringType->qualifiedName(), $this->definition->name);
	}

	public function __toString(): string
	{
		return $this->name();
	}
}
