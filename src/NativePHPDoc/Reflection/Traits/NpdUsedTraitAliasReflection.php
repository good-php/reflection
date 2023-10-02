<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Traits;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitAliasDefinition;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitAliasReflection;

final class NpdUsedTraitAliasReflection implements UsedTraitAliasReflection
{
	public function __construct(
		private readonly UsedTraitAliasDefinition $definition,
	) {}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function newName(): ?string
	{
		return $this->definition->newName;
	}

	public function newModifier(): ?int
	{
		return $this->definition->newModifier;
	}
}
