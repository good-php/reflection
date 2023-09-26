<?php

namespace GoodPhp\Reflection\Reflector;

use GoodPhp\Reflection\Definition\DefinitionProvider;
use GoodPhp\Reflection\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\Reflector\Reflection\TypeReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeComparator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Reflector
{
	public readonly TypeComparator $typeComparator;

	public function __construct(
		private readonly DefinitionProvider $definitionProvider,
	) {
		$this->typeComparator = new TypeComparator($this);
	}

	/**
	 * @return TypeReflection<mixed>
	 */
	public function forNamedType(NamedType $type): TypeReflection
	{
		$definition = $this->definitionProvider->forType($type->name) ??
			throw new UnknownTypeException($type->name);

		$resolvedTypeParameterMap = match (true) {
			$definition instanceof ClassTypeDefinition ||
			$definition instanceof InterfaceTypeDefinition ||
			$definition instanceof TraitTypeDefinition ||
			$definition instanceof SpecialTypeDefinition => TypeParameterMap::fromArguments($type->arguments->all(), $definition->typeParameters),
			default                                      => TypeParameterMap::empty()
		};

		return match (true) {
			$definition instanceof ClassTypeDefinition     => new Reflection\ClassReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof InterfaceTypeDefinition => new Reflection\InterfaceReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof TraitTypeDefinition     => new Reflection\TraitReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof EnumTypeDefinition      => new Reflection\EnumReflection($definition, $this),
			$definition instanceof SpecialTypeDefinition   => new Reflection\SpecialTypeReflection($definition, $resolvedTypeParameterMap),
			default                                        => throw new InvalidArgumentException('Unsupported definition of type ' . $definition::class . ' given.')
		};
	}

	/**
	 * @param Collection<int, Type> $arguments
	 *
	 * @return TypeReflection<mixed>
	 */
	public function forType(string $name, Collection $arguments = new Collection()): TypeReflection
	{
		return $this->forNamedType(new NamedType($name, $arguments));
	}
}
