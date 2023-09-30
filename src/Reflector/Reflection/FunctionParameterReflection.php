<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionParameter;
use Stringable;
use Webmozart\Assert\Assert;

/**
 * @template-covariant DeclaringMethodReflection of MethodReflection
 */
final class FunctionParameterReflection implements Stringable, HasAttributes
{
	private readonly ReflectionParameter $nativeReflection;

	private readonly Attributes $attributes;

	private readonly ?Type $type;

	/**
	 * @param DeclaringMethodReflection $declaringMethod
	 */
	public function __construct(
		private readonly FunctionParameterDefinition $definition,
		public readonly MethodReflection $declaringMethod,
		public NamedType $staticType,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {}

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
		// because a parameter might have a default value of `null` too, and they wouldn't be able to distinguish the two
		// without first calling the `->hasDefaultValue()`. So to avoid confusion, this assert is in place.
		Assert::true($this->hasDefaultValue(), 'Parameter does not have a default value; you must first check if default value is set through ->hasDefaultValue().');

		return $this->nativeReflection()->getDefaultValue();
	}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new Attributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	public function location(): string
	{
		return $this->declaringMethod->location() . ' ' . $this;
	}

	private function nativeReflection(): ReflectionParameter
	{
		return $this->nativeReflection ??= new ReflectionParameter(
			[$this->declaringMethod->declaringType->qualifiedName(), $this->declaringMethod->name()],
			$this->definition->name
		);
	}

	public function __toString(): string
	{
		return 'arg $' . $this->name();
	}
}
