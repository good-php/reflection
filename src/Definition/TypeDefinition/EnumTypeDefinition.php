<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;

/**
 * @template-covariant T of \UnitEnum
 */
final class EnumTypeDefinition extends TypeDefinition
{
	/** @var class-string<T> */
	public readonly string $qualifiedName;

	/**
	 * @param class-string<T>                     $qualifiedName
	 * @param Collection<int, NamedType>          $implements
	 * @param Collection<int, NamedType>          $uses
	 * @param Collection<int, EnumCaseDefinition> $cases
	 * @param Collection<int, MethodDefinition>   $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly ?NamedType $backingType,
		public readonly Collection $implements,
		public readonly Collection $uses,
		public readonly Collection $cases,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
