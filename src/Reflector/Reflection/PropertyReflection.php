<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionProperty;
use TenantCloud\Standard\Lazy\Lazy;
use UnitEnum;
use Webmozart\Assert\Assert;

use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant DeclaringTypeReflection of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
final class PropertyReflection implements HasAttributes
{
	/** @var Lazy<ReflectionProperty> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<Type|null> */
	private readonly Lazy $type;

	/** @var Lazy<FunctionParameterReflection<MethodReflection<ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>|null> */
	private readonly Lazy $promotedParameter;

	/**
	 * @param DeclaringTypeReflection $declaringType
	 */
	public function __construct(
		private readonly PropertyDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $declaringType,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->nativeReflection = lazy(fn () => new ReflectionProperty($this->declaringType->qualifiedName(), $this->definition->name));
		$this->attributes = lazy(fn () => new Attributes(
			fn () => $this->nativeReflection->value()->getAttributes()
		));
		$this->type = lazy(
			fn () => $this->definition->type ?
				TypeProjector::templateTypes(
					$this->definition->type,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->promotedParameter = lazy(
			function () {
				if (!$this->definition->isPromoted || !$this->declaringType instanceof ClassReflection) {
					return null;
				}

				$constructor = $this->declaringType->constructor();

				Assert::notNull($constructor);

				return $constructor->parameters()->first(
					fn (FunctionParameterReflection $parameter) => $this->definition->name === $parameter->name()
				);
			}
		);
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function type(): ?Type
	{
		return $this->type->value();
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

		return $this->nativeReflection->value()->getDefaultValue();
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
		if (!$this->isPromoted()) {
			return null;
		}

		return $this->promotedParameter->value();
	}

	public function attributes(): Attributes
	{
		return $this->attributes->value();
	}

	public function get(object $receiver): mixed
	{
		return $this->nativeReflection->value()->getValue($receiver);
	}

	/**
	 * Set a property with strict_types=0.
	 */
	public function set(object $receiver, mixed $value): void
	{
		$this->nativeReflection->value()->setValue($receiver, $value);
	}
}
