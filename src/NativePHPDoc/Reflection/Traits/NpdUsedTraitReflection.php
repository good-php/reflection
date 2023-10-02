<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Traits;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitAliasDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;

final class NpdUsedTraitReflection implements UsedTraitReflection
{
	private readonly NamedType $trait;

	/** @var Collection<int, NpdUsedTraitAliasReflection> */
	private readonly Collection $aliases;

	public function __construct(
		private readonly UsedTraitDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private NamedType $staticType,
	) {}

	public function trait(): NamedType
	{
		return $this->trait ??= TypeProjector::templateTypes(
			$this->definition->trait,
			$this->resolvedTypeParameterMap,
			$this->staticType,
		);
	}

	/**
	 * @return Collection<int, NpdUsedTraitAliasReflection>
	 */
	public function aliases(): Collection
	{
		return $this->aliases ??= $this->definition
			->aliases
			->map(fn (UsedTraitAliasDefinition $alias) => new NpdUsedTraitAliasReflection($alias));
	}
}
