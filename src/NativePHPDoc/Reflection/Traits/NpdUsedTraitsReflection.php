<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Traits;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;

final class NpdUsedTraitsReflection implements UsedTraitsReflection
{
	/** @var Collection<int, NpdUsedTraitReflection> */
	private readonly Collection $traits;

	public function __construct(
		private readonly UsedTraitsDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private NamedType $staticType,
	) {}

	/**
	 * @return Collection<int, NpdUsedTraitReflection>
	 */
	public function traits(): Collection
	{
		return $this->traits ??= $this->definition
			->traits
			->map(fn (UsedTraitDefinition $trait) => new NpdUsedTraitReflection($trait, $this->resolvedTypeParameterMap, $this->staticType));
	}

	/**
	 * @return Collection<class-string, Collection<int, string>>
	 */
	public function excludedTraitMethods(): Collection
	{
		return $this->definition->excludedTraitMethods;
	}
}
