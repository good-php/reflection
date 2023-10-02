<?php

namespace GoodPhp\Reflection\Reflection\Traits;

interface UsedTraitAliasReflection
{
	public function name(): string;

	public function newName(): ?string;

	public function newModifier(): ?int;
}
