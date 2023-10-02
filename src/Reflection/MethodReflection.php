<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;
use Stringable;

/**
 * @template-covariant DeclaringTypeReflection of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
interface MethodReflection extends Stringable, HasAttributes, HasTypeParameters
{
	public function withStaticType(NamedType $staticType): static;

	public function name(): string;

	/**
	 * @return Collection<int, FunctionParameterReflection<$this>>
	 */
	public function parameters(): Collection;

	public function returnType(): ?Type;

	/**
	 * Call a method with strict_types=1.
	 */
	public function invoke(object $receiver, mixed ...$args): mixed;

	/**
	 * Call a public method with strict_types=0.
	 */
	public function invokeLax(object $receiver, mixed ...$args): mixed;

	public function location(): string;
}
