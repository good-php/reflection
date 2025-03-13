<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Traits;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;

final class NpdUsedTraitsReflection implements UsedTraitsReflection
{
	/** @var list<NpdUsedTraitReflection> */
	private readonly array $traits;

	public function __construct(
		private readonly UsedTraitsDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private NamedType $staticType,
	) {}

	/**
	 * @return list<NpdUsedTraitReflection>
	 */
	public function traits(): array
	{
		return $this->traits ??= array_map(
			fn (UsedTraitDefinition $trait) => new NpdUsedTraitReflection($trait, $this->resolvedTypeParameterMap, $this->staticType),
			$this->definition->traits,
		);
	}

	/**
	 * @return array<class-string, list<string>>
	 */
	public function excludedTraitMethods(): array
	{
		return $this->definition->excludedTraitMethods;
	}
}
