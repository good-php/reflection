<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionProperty;
use Stringable;
use UnitEnum;
use Webmozart\Assert\Assert;

/**
 * @template-covariant DeclaringTypeReflection of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
final class PropertyReflection implements Stringable, HasAttributes
{
	private readonly ReflectionProperty $nativeReflection;

	private readonly Attributes $attributes;

	private readonly ?Type $type;

	/** @var FunctionParameterReflection<MethodReflection<ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>|null */
	private readonly ?FunctionParameterReflection $promotedParameter;

	/**
	 * @param DeclaringTypeReflection $declaringType
	 */
	public function __construct(
		private readonly PropertyDefinition $definition,
		private readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $declaringType,
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
	 * @return FunctionParameterReflection<MethodReflection<ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>|null
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
		return $this->attributes ??= new Attributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	public function get(object $receiver): mixed
	{
		return $this->nativeReflection()->getValue($receiver);
	}

	/**
	 * Set a property with strict_types=0.
	 */
	public function set(object $receiver, mixed $value): void
	{
		$this->nativeReflection()->setValue($receiver, $value);
	}

	public function location(): string
	{
		return $this->declaringType->location() . '::' . $this;
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
