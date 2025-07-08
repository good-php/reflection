<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @template-contravariant ReflectableType of object
 *
 * @template-covariant DeclaringTypeReflection of HasMethods<ReflectableType>
 *
 * @extends HasTypeParameters<self<ReflectableType, DeclaringTypeReflection>>
 */
interface MethodReflection extends Stringable, HasAttributes, HasTypeParameters
{
	public function withStaticType(NamedType $staticType): static;

	public function name(): string;

	/**
	 * @return list<FunctionParameterReflection<self<ReflectableType, DeclaringTypeReflection>>>
	 */
	public function parameters(): array;

	public function returnType(): ?Type;

	/**
	 * Call a method with strict_types=1.
	 *
	 * @param ReflectableType $receiver
	 */
	public function invoke(object $receiver, mixed ...$args): mixed;

	/**
	 * Call a public method with strict_types=0.
	 *
	 * @param ReflectableType $receiver
	 */
	public function invokeLax(object $receiver, mixed ...$args): mixed;

	public function location(): string;

	/**
	 * @return DeclaringTypeReflection
	 */
	public function declaringType(): HasMethods;
}
