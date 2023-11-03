<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionProperty;
use Webmozart\Assert\Assert;

/**
 * @template-contravariant ReflectableType of object
 *
 * @template-covariant DeclaringTypeReflection of HasProperties<ReflectableType>
 *
 * @implements PropertyReflection<ReflectableType, DeclaringTypeReflection>
 */
final class NpdPropertyReflection implements PropertyReflection
{
	private readonly ReflectionProperty $nativeReflection;

	private readonly Attributes $attributes;

	private readonly ?Type $type;

	/** @var FunctionParameterReflection<MethodReflection<object, HasMethods<object>>>|null */
	private readonly ?FunctionParameterReflection $promotedParameter;

	/**
	 * @param DeclaringTypeReflection $declaringType
	 */
	public function __construct(
		private readonly PropertyDefinition $definition,
		private readonly HasProperties $declaringType,
		private NamedType $staticType,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
	) {}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($this->type, $that->promotedParameter);

		return $that;
	}

	public function name(): string
	{
		return $this->definition->name;
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
			$this->resolvedTypeParameterMap,
			$this->staticType,
		);
	}

	public function hasDefaultValue(): bool
	{
		return $this->definition->hasDefaultValue;
	}

	public function defaultValue(): mixed
	{
		// I could have simply returned `null` in this case, but that would likely lead to developer errors on the other end
		// because a property might have a default value of `null` too, and they wouldn't be able to distinguish the two
		// without first calling the `->hasDefaultValue()`. So to avoid confusion, this assert is in place.
		Assert::true($this->hasDefaultValue(), 'Property does not have a default value; you must first check if default value is set through ->hasDefaultValue().');

		return $this->nativeReflection()->getDefaultValue();
	}

	public function isPromoted(): bool
	{
		return $this->definition->isPromoted;
	}

	/**
	 * If property is promoted, it refers to the __construct parameter it was promoted for.
	 *
	 * @return FunctionParameterReflection<MethodReflection<object, HasMethods<object>>>|null
	 */
	public function promotedParameter(): FunctionParameterReflection|null
	{
		if (isset($this->promotedParameter)) {
			return $this->promotedParameter;
		}

		if (!$this->isPromoted() || !$this->declaringType instanceof ClassReflection) {
			return null;
		}

		$constructor = $this->declaringType->constructor();

		Assert::notNull($constructor);

		return $this->promotedParameter ??= $constructor->parameters()->first(
			fn (FunctionParameterReflection $parameter) => $this->definition->name === $parameter->name()
		);
	}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	public function get(object $receiver): mixed
	{
		return $this->nativeReflection()->getValue($receiver);
	}

	public function set(object $receiver, mixed $value): void
	{
		$propertyName = $this->name();

		(fn () => $this->{$propertyName} = $value)->call($receiver);
	}

	public function setLax(object $receiver, mixed $value): void
	{
		$this->nativeReflection()->setValue($receiver, $value);
	}

	public function location(): string
	{
		return $this->declaringType->location() . '::' . $this;
	}

	/**
	 * @return DeclaringTypeReflection
	 */
	public function declaringType(): HasProperties
	{
		return $this->declaringType;
	}

	private function nativeReflection(): ReflectionProperty
	{
		return $this->nativeReflection ??= new ReflectionProperty($this->declaringType->qualifiedName(), $this->definition->name);
	}

	public function __toString(): string
	{
		return '$' . $this->name();
	}
}
