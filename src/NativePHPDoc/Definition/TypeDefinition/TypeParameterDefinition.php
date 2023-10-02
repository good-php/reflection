<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Type;
use Stringable;

/**
 * @see TypeParameterDefinitionTest
 */
final class TypeParameterDefinition implements Stringable
{
	public function __construct(
		public readonly string $name,
		public readonly bool $variadic,
		public readonly ?Type $upperBound,
		public readonly TemplateTypeVariance $variance,
	) {}

	public function __toString(): string
	{
		$result = [
			match ($this->variance) {
				TemplateTypeVariance::INVARIANT     => '',
				TemplateTypeVariance::CONTRAVARIANT => 'in',
				TemplateTypeVariance::COVARIANT     => 'out',
			},
			($this->variadic ? '...' : '') . $this->name,
			$this->upperBound ? "of {$this->upperBound}" : '',
		];

		return implode(' ', array_filter($result));
	}
}
