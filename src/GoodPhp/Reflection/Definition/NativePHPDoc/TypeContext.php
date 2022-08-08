<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc;

use GoodPhp\Reflection\Definition\NativePHPDoc\File\FileClassLikeContext;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;
use TenantCloud\Standard\Lazy\Lazy;

class TypeContext
{
	/**
	 * @param Collection<string, Lazy<TypeParameterDefinition>> $typeParameters
	 */
	public function __construct(
		public readonly ?FileClassLikeContext $fileClassLikeContext,
		public readonly NamedType $definingType,
		public readonly Collection $typeParameters
	) {
	}

	/**
	 * @param Collection<string, Lazy<TypeParameterDefinition>> $parameters
	 */
	public function withMergedTypeParameters(Collection $parameters): self
	{
		return new self(
			fileClassLikeContext: $this->fileClassLikeContext,
			definingType: $this->definingType,
			typeParameters: (clone $this->typeParameters)->merge($parameters)
		);
	}
}
