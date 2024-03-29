<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
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
		public readonly NamedType $declaringType,
		public readonly ?NamedType $declaringTypeParent,
		public readonly Collection $typeParameters
	) {}

	/**
	 * @param Collection<string, Lazy<TypeParameterDefinition>> $parameters
	 */
	public function withMergedTypeParameters(Collection $parameters): self
	{
		return new self(
			fileClassLikeContext: $this->fileClassLikeContext,
			declaringType: $this->declaringType,
			declaringTypeParent: $this->declaringTypeParent,
			typeParameters: (clone $this->typeParameters)->merge($parameters)
		);
	}
}
