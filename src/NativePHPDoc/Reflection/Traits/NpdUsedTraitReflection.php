<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Traits;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitAliasDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;

final class NpdUsedTraitReflection implements UsedTraitReflection
{
	private readonly NamedType $trait;

	/** @var list<NpdUsedTraitAliasReflection> */
	private readonly array $aliases;

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
	 * @return list<NpdUsedTraitAliasReflection>
	 */
	public function aliases(): array
	{
		return $this->aliases ??= array_map(
			fn (UsedTraitAliasDefinition $alias) => new NpdUsedTraitAliasReflection($alias),
			$this->definition->aliases,
		);
	}
}
