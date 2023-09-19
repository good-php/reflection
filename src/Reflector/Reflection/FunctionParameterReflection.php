<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionParameter;
use TenantCloud\Standard\Lazy\Lazy;
use Webmozart\Assert\Assert;

use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of MethodReflection
 */
class FunctionParameterReflection implements HasAttributes
{
	/** @var Lazy<ReflectionParameter> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<Type|null> */
	private Lazy $type;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly FunctionParameterDefinition $definition,
		public readonly MethodReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->nativeReflection = lazy(fn () => new ReflectionParameter([$this->owner->owner->qualifiedName(), $this->owner->name()], $this->definition->name));
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
		// because a parameter might have a default value of `null` too, and they wouldn't be able to distinguish the two
		// without first calling the `->hasDefaultValue()`. So to avoid confusion, this assert is in place.
		Assert::true($this->hasDefaultValue(), 'Parameter does not have a default value; you must first check if default value is set through ->hasDefaultValue().');

		return $this->nativeReflection->value()->getDefaultValue();
	}

	public function attributes(): Attributes
	{
		return $this->attributes->value();
	}
}
