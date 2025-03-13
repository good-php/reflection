<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Util\Lazy\Lazy;

class TypeContext
{
	/**
	 * @param array<string, Lazy<TypeParameterDefinition>> $typeParameters
	 */
	public function __construct(
		public readonly ?FileClassLikeContext $fileClassLikeContext,
		public readonly NamedType $declaringType,
		public readonly ?NamedType $declaringTypeParent,
		public readonly array $typeParameters
	) {}

	/**
	 * @param array<string, Lazy<TypeParameterDefinition>> $parameters
	 */
	public function withMergedTypeParameters(array $parameters): self
	{
		return new self(
			fileClassLikeContext: $this->fileClassLikeContext,
			declaringType: $this->declaringType,
			declaringTypeParent: $this->declaringTypeParent,
			typeParameters: [
				...$this->typeParameters,
				...$parameters,
			],
		);
	}
}
