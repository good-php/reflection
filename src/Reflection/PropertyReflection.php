<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @template-contravariant ReflectableType of object
 * @template-covariant DeclaringTypeReflection of HasProperties<ReflectableType>
 */
interface PropertyReflection extends Stringable, HasAttributes
{
	public function withStaticType(NamedType $staticType): static;

	public function name(): string;

	public function type(): ?Type;

	public function hasDefaultValue(): bool;

	public function defaultValue(): mixed;

	public function isPromoted(): bool;

	/**
	 * If property is promoted, it refers to the __construct parameter it was promoted for.
	 *
	 * @return FunctionParameterReflection<MethodReflection<HasMethods>>|null
	 */
	public function promotedParameter(): FunctionParameterReflection|null;

	/**
	 * @param ReflectableType $receiver
	 */
	public function get(object $receiver): mixed;

	/**
	 * Set a property with strict_types=1.
	 *
	 * @param ReflectableType $receiver
	 */
	public function set(object $receiver, mixed $value): void;

	/**
	 * Set a property with strict_types=0.
	 *
	 * @param ReflectableType $receiver
	 */
	public function setLax(object $receiver, mixed $value): void;

	public function location(): string;

	/**
	 * @return DeclaringTypeReflection
	 */
	public function declaringType(): HasProperties;
}
