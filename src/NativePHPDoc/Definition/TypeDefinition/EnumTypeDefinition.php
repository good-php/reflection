<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;

/**
 * @template-covariant T of \UnitEnum
 */
final class EnumTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>          $qualifiedName
	 * @param list<NamedType>          $implements
	 * @param list<EnumCaseDefinition> $cases
	 * @param list<MethodDefinition>   $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly ?NamedType $backingType,
		public readonly array $implements,
		public readonly UsedTraitsDefinition $uses,
		public readonly array $cases,
		public readonly array $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
