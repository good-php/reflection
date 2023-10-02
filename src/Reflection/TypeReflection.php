<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Type\NamedType;
use Stringable;

/**
 * @template-covariant T
 */
interface TypeReflection extends Stringable
{
	public function fileName(): ?string;

	public function qualifiedName(): string;

	public function shortName(): string;

	public function type(): NamedType;

	public function location(): string;
}
