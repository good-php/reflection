<?php

namespace GoodPhp\Reflection\Reflection\Traits;

use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

final class TraitAliasesMethodReflection implements MethodReflection
{
	public function __construct(
		private MethodReflection $method,
		private readonly UsedTraitAliasReflection $alias,
	) {
		// $methodModifiers = ($methodModifiers & ~ Node\Stmt\Class_::VISIBILITY_MODIFIER_MASK) | $this->alias->newModifier();
	}

	public function withStaticType(NamedType $staticType): static
	{
		$that = clone $this;
		$that->method = $this->method->withStaticType($staticType);

		return $that;
	}

	public function name(): string
	{
		return $this->alias->newName() ?? $this->method->name();
	}

	public function attributes(): Attributes
	{
		return $this->method->attributes();
	}

	public function typeParameters(): Collection
	{
		return $this->method->typeParameters();
	}

	public function parameters(): Collection
	{
		return $this->method->parameters();
	}

	public function returnType(): ?Type
	{
		return $this->method->returnType();
	}

	public function invoke(object $receiver, ...$args): mixed
	{
		return $this->method->invoke($receiver, ...$args);
	}

	public function invokeLax(object $receiver, ...$args): mixed
	{
		return $this->method->invokeLax($receiver, ...$args);
	}

	public function location(): string
	{
		return $this->method->location();
	}

	public function __toString(): string
	{
		return $this->name() . '()';
	}
}
