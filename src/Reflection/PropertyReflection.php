<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Stringable;
use UnitEnum;

/**
 * @template-covariant DeclaringTypeReflection of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
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
	 * @return FunctionParameterReflection<MethodReflection<ClassReflection<object>|InterfaceReflection<object>|TraitReflection<object>|EnumReflection<UnitEnum>>>|null
	 */
	public function promotedParameter(): FunctionParameterReflection|null;

	public function get(object $receiver): mixed;

	/**
	 * Set a property with strict_types=1.
	 */
	public function set(object $receiver, mixed $value): void;

	/**
	 * Set a property with strict_types=0.
	 */
	public function setLax(object $receiver, mixed $value): void;

	public function location(): string;
}
