<?php

namespace GoodPhp\Reflection\Definition\TypeDefinition;

use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

final class EnumTypeDefinition extends TypeDefinition
{
	/**
	 * @param Collection<int, Type>             $implements
	 * @param Collection<int, Type>             $uses
	 * @param Collection<int, MethodDefinition> $methods
	 */
	public function __construct(
		string $qualifiedName,
		?string $fileName,
		public readonly bool $builtIn,
		public readonly ?Type $backingType,
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
