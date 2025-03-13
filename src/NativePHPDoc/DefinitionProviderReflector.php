<?php

namespace GoodPhp\Reflection\NativePHPDoc;

use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\SpecialTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\NpdClassReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\NpdEnumReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\NpdInterfaceReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\NpdSpecialTypeReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\NpdTraitReflection;
use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeComparator;
use GoodPhp\Reflection\UnknownTypeException;
use InvalidArgumentException;

final class DefinitionProviderReflector implements Reflector
{
	private readonly TypeComparator $typeComparator;

	public function __construct(
		private readonly DefinitionProvider $definitionProvider,
	) {
		$this->typeComparator = new TypeComparator($this);
	}

	public function typeComparator(): TypeComparator
	{
		return $this->typeComparator;
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
			$definition instanceof SpecialTypeDefinition => TypeParameterMap::fromArguments($type->arguments, $definition->typeParameters),
			default                                      => TypeParameterMap::empty()
		};

		return match (true) {
			$definition instanceof ClassTypeDefinition     => new NpdClassReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof InterfaceTypeDefinition => new NpdInterfaceReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof TraitTypeDefinition     => new NpdTraitReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof EnumTypeDefinition      => new NpdEnumReflection($definition, $this),
			$definition instanceof SpecialTypeDefinition   => new NpdSpecialTypeReflection($definition, $resolvedTypeParameterMap),
			default                                        => throw new InvalidArgumentException('Unsupported definition of type ' . $definition::class . ' given.')
		};
	}

	/**
	 * @param list<Type> $arguments
	 *
	 * @return TypeReflection<mixed>
	 */
	public function forType(string $name, array $arguments = []): TypeReflection
	{
		return $this->forNamedType(new NamedType($name, $arguments));
	}
}
