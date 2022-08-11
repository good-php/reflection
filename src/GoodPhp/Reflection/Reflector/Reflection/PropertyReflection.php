<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionProperty;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
class PropertyReflection implements HasAttributes
{
	/** @var Lazy<Type|null> */
	private readonly Lazy $type;

	private readonly ReflectionProperty $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly PropertyDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->type = lazy(
			fn () => $this->definition->type ?
				TypeProjector::templateTypes(
					$this->definition->type,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->nativeReflection = new ReflectionProperty($this->owner->qualifiedName(), $this->definition->name);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function type(): ?Type
	{
		return $this->type->value();
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	public function get(object $receiver)
	{
		return $this->nativeReflection->getValue($receiver);
	}

	/**
	 * Set a property with strict_types=0.
	 */
	public function set(object $receiver, mixed $value): void
	{
		$this->nativeReflection->setValue($receiver, $value);
	}

	/**
	 * Set a public property with strict_types=1.
	 */
	public function setStrict(object $receiver, mixed $value): void
	{
		$receiver->{$this->name()} = $value;
	}
}
