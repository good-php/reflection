<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc;

use BackedEnum;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\Native\NativeTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\ParsedPhpDoc;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\TypeAliasResolver;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumCaseDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitAliasDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Util\Lazy\LateInitLazy;
use GoodPhp\Reflection\Util\Lazy\Lazy;
use GoodPhp\Reflection\Util\ReflectionAssert;
use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\UsesTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use UnitEnum;
use Webmozart\Assert\Assert;

use function GoodPhp\Reflection\Util\Lazy\lazy;

class NativePHPDocDefinitionProvider implements DefinitionProvider
{
	public function __construct(
		private readonly PhpDocStringParser $phpDocStringParser,
		private readonly FileContextParser $fileContextParser,
		private readonly TypeAliasResolver $typeAliasResolver,
		private readonly NativeTypeMapper $nativeTypeMapper,
		private readonly PhpDocTypeMapper $phpDocTypeMapper
	) {}

	public function forType(string $type): ?TypeDefinition
	{
		return match (true) {
			// enum_exists() MUST come first because for whatever reason class_exists() returns true for enums
			enum_exists($type) => $this->forEnum($type),
			class_exists($type), interface_exists($type), trait_exists($type) => $this->forClassLike($type),
			default => null
		};
	}

	/**
	 * @return array{ Type|null, TypeSource|null }
	 */
	public function map(
		ReflectionType|string|null $nativeType,
		?TypeNode $phpDocType,
		TypeContext $context
	): array {
		if ($phpDocType) {
			return [
				$this->phpDocTypeMapper->map($phpDocType, $context),
				TypeSource::PHP_DOC,
			];
		}

		if ($nativeType) {
			return [
				$this->nativeTypeMapper->map($nativeType, $context),
				TypeSource::NATIVE,
			];
		}

		return [null, null];
	}

	/**
	 * @param class-string<object> $type
	 */
	private function forClassLike(string $type): TypeDefinition
	{
		$reflection = new ReflectionClass($type);

		$phpDoc = $this->phpDocStringParser->parse($reflection);
		$context = $this->createTypeContext($reflection, $phpDoc);

		return match (true) {
			$reflection->isTrait() => new TraitTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				typeParameters: $this->typeParameters($phpDoc, $context),
				uses: $this->traits($reflection, $context),
				properties: $this->properties($reflection, $context),
				methods: $this->methods($reflection, $context),
			),
			$reflection->isInterface() => new InterfaceTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				typeParameters: $this->typeParameters($phpDoc, $context),
				extends: $this->interfaces($reflection, $phpDoc, $context),
				methods: $this->methods($reflection, $context),
			),
			default => new ClassTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				anonymous: $reflection->isAnonymous(),
				final: $reflection->isFinal(),
				abstract: $reflection->isAbstract(),
				readOnly: $reflection->isReadOnly(),
				cloneable: $reflection->isCloneable(),
				instantiable: $reflection->isInstantiable(),
				typeParameters: $this->typeParameters($phpDoc, $context),
				extends: $this->parent($reflection, $phpDoc, $context),
				implements: $this->interfaces($reflection, $phpDoc, $context),
				uses: $this->traits($reflection, $context),
				properties: $this->properties($reflection, $context),
				methods: $this->methods($reflection, $context),
			)
		};
	}

	/**
	 * @param class-string<UnitEnum> $type
	 */
	private function forEnum(string $type): TypeDefinition
	{
		$reflection = new ReflectionEnum($type);

		$phpDoc = $this->phpDocStringParser->parse($reflection);
		$context = $this->createTypeContext($reflection, $phpDoc);
		$backingType = $reflection->getBackingType() ? $this->nativeTypeMapper->map($reflection->getBackingType(), $context) : null;
		$implicitInterfaces = $backingType ? [new NamedType(BackedEnum::class)] : [new NamedType(UnitEnum::class)];

		Assert::nullOrIsInstanceOf($backingType, NamedType::class);

		return new EnumTypeDefinition(
			qualifiedName: $this->qualifiedName($reflection),
			fileName: $this->fileName($reflection),
			builtIn: !$reflection->isUserDefined(),
			backingType: $backingType,
			implements: [...$this->interfaces($reflection, $phpDoc, $context), ...$implicitInterfaces],
			uses: $this->traits($reflection, $context),
			cases: $this->enumCases($reflection),
			methods: $this->methods($reflection, $context),
		);
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 */
	private function createTypeContext(ReflectionClass $reflection, ParsedPhpDoc $phpDoc): TypeContext
	{
		$context = new TypeContext(
			fileClassLikeContext: $this->fileContextParser
				->parse($reflection)
				?->forClassLike($reflection),
			declaringType: new NamedType($reflection->getName()),
			declaringTypeParent: $reflection->getParentClass() ? new NamedType($reflection->getParentClass()->getName()) : null,
			typeParameters: []
		);

		$lazyTypeParameters = $this->lazyTypeParameters($phpDoc, $context);

		return $context->withMergedTypeParameters($lazyTypeParameters);
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 *
	 * @return ($reflection is ReflectionEnum<UnitEnum> ? class-string<UnitEnum> : class-string<object>)
	 */
	private function qualifiedName(ReflectionClass $reflection): string
	{
		return $reflection->getName();
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 */
	private function fileName(ReflectionClass $reflection): ?string
	{
		return $reflection->getFileName() ?: null;
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return list<PropertyDefinition>
	 */
	private function properties(ReflectionClass $reflection, TypeContext $context): array
	{
		$constructorPhpDoc = $this->phpDocStringParser->parse(
			$reflection->getConstructor()?->getDocComment() ?: ''
		);

		return collect($reflection->getProperties())
			->filter(fn (ReflectionProperty $property) => !$context->fileClassLikeContext || in_array($property->getName(), $context->fileClassLikeContext->declaredProperties, true))
			->map(function (ReflectionProperty $property) use ($context, $constructorPhpDoc) {
				$phpDoc = $this->phpDocStringParser->parse($property);

				// Get first @var tag (if any specified). Works for both regular and promoted properties.
				$phpDocType = $phpDoc->firstVarTagValue()?->type;

				// If none found, fallback to @param tag if it's a promoted property. The check for promoted property
				// is important because there could be a property with the same name as a parameter, but those being unrelated.
				if (!$phpDocType && $property->isPromoted()) {
					$phpDocType = $constructorPhpDoc->firstParamTagValue($property->getName())?->type;
				}

				[$type, $typeSource] = $this->map(
					$property->getType(),
					$phpDocType,
					$context
				);

				return new PropertyDefinition(
					name: $property->getName(),
					type: $type,
					typeSource: $typeSource,
					hasDefaultValue: $property->hasDefaultValue(),
					isPromoted: $property->isPromoted(),
				);
			})
			->values()
			->all();
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 *
	 * @return list<MethodDefinition>
	 */
	private function methods(ReflectionClass $reflection, TypeContext $context): array
	{
		return collect($reflection->getMethods())
			->filter(fn (ReflectionMethod $method) => !$context->fileClassLikeContext || in_array($method->getName(), $context->fileClassLikeContext->declaredMethods, true))
			->map(function (ReflectionMethod $method) use ($context) {
				$phpDoc = $this->phpDocStringParser->parse($method);

				// Get first @return tag (if any specified).
				$phpDocType = $phpDoc->firstReturnTagValue()?->type;

				$context = $context->withMergedTypeParameters(
					$this->lazyTypeParameters($phpDoc, $context)
				);

				[$returnType, $returnTypeSource] = $this->map(
					$method->getReturnType(),
					$phpDocType,
					$context,
				);

				return new MethodDefinition(
					name: $method->getName(),
					typeParameters: $this->typeParameters($phpDoc, $context),
					parameters: $this->functionParameters($method, $phpDoc, $context),
					returnType: $returnType,
					returnTypeSource: $returnTypeSource,
				);
			})
			->values()
			->all();
	}

	/**
	 * @return array<string, Lazy<TypeParameterDefinition>>
	 */
	private function lazyTypeParameters(ParsedPhpDoc $phpDoc, TypeContext $context): array
	{
		/** @var array<string, Lazy<TypeParameterDefinition>> $lazyTypeParametersMap */
		$lazyTypeParametersMap = [];
		/** @var LateInitLazy<TypeContext> $temporaryContext */
		$temporaryContext = new LateInitLazy();

		// For whatever reason phpstan/phpdoc-parser doesn't parse the differences between @template and @template-covariant,
		// so instead of using ->getTemplateTagValues() we'll filter tags manually.
		foreach ($phpDoc->templateTags() as $node) {
			/** @var TemplateTagValueNode $value */
			$value = $node->value;

			$lazyTypeParametersMap[$value->name] = lazy(
				function () use ($node, $value, $temporaryContext) {
					return new TypeParameterDefinition(
						name: $value->name,
						variadic: false,
						upperBound: $value->bound ?
							$this->phpDocTypeMapper->map(
								$value->bound,
								$temporaryContext->value(),
							) :
							null,
						variance: match (true) {
							Str::endsWith($node->name, '-covariant')     => TemplateTypeVariance::COVARIANT,
							Str::endsWith($node->name, '-contravariant') => TemplateTypeVariance::CONTRAVARIANT,
							default                                      => TemplateTypeVariance::INVARIANT
						},
						default: $value->default ?
							$this->phpDocTypeMapper->map(
								$value->default,
								$temporaryContext->value(),
							) :
							null,
					);
				}
			);
		}

		$temporaryContext->initialize($context->withMergedTypeParameters($lazyTypeParametersMap));

		return $lazyTypeParametersMap;
	}

	/**
	 * @return list<TypeParameterDefinition>
	 */
	private function typeParameters(ParsedPhpDoc $phpDoc, TypeContext $context): array
	{
		// The types are properly defined, but for whatever reason ->map() breaks it all.
		return array_map(
			fn (Lazy $lazy) => $lazy->value(),
			array_values($this->lazyTypeParameters($phpDoc, $context))
		);
	}

	/**
	 * @return list<FunctionParameterDefinition>
	 */
	private function functionParameters(ReflectionMethod $reflection, ParsedPhpDoc $phpDoc, TypeContext $context): array
	{
		return collect($reflection->getParameters())
			->map(function (ReflectionParameter $parameter) use ($context, $phpDoc) {
				$paramTag = $phpDoc->firstParamTagValue($parameter->getName());

				[$type, $typeSource] = $this->map(
					$parameter->getType(),
					$paramTag?->type,
					$context
				);

				return new FunctionParameterDefinition(
					name: $parameter->getName(),
					type: $type,
					typeSource: $typeSource,
					hasDefaultValue: $parameter->isDefaultValueAvailable(),
				);
			})
			->all();
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function parent(ReflectionClass $reflection, ParsedPhpDoc $phpDoc, TypeContext $context): ?NamedType
	{
		$parentClass = $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null;

		if (!$parentClass) {
			return null;
		}

		$tagValue = $phpDoc->firstExtendsTagValue(
			fn (ExtendsTagValueNode $node) => $parentClass === $this->typeAliasResolver->resolve($node->type->type->name, $context->fileClassLikeContext)
		);

		[$type] = $this->map(
			$parentClass,
			$tagValue?->type,
			$context
		);

		if (!$type) {
			return null;
		}

		ReflectionAssert::namedType($type, 'mapping `extends` type for [' . $reflection->getName() . ']');

		return $type;
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 *
	 * @return list<NamedType>
	 */
	private function interfaces(ReflectionClass $reflection, ParsedPhpDoc $phpDoc, TypeContext $context): array
	{
		return collect($reflection->getInterfaceNames())
			->filter(fn (string $className) => !$context->fileClassLikeContext || in_array($className, $context->fileClassLikeContext->implementsInterfaces, true))
			->map(function (string $className) use ($reflection, $context, $phpDoc) {
				$tagValue = $phpDoc->firstImplementsTagValue(
					fn (ImplementsTagValueNode $node) => $className === $this->typeAliasResolver->resolve($node->type->type->name, $context->fileClassLikeContext)
				);
				$tagValue ??= $phpDoc->firstExtendsTagValue(
					fn (ExtendsTagValueNode $node) => $className === $this->typeAliasResolver->resolve($node->type->type->name, $context->fileClassLikeContext)
				);

				[$type] = $this->map(
					$className,
					$tagValue?->type,
					$context
				);

				Assert::notNull($type);
				ReflectionAssert::namedType($type, 'mapping `implements` types for [' . $reflection->getName() . ']');

				return $type;
			})
			->values()
			->all();
	}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 */
	private function traits(ReflectionClass $reflection, TypeContext $context): UsedTraitsDefinition
	{
		if ($context->fileClassLikeContext) {
			$traitsUses = $context
				->fileClassLikeContext
				->traitsUses;
		} else {
			$traitsUses = [
				new TraitsUse(
					array_map(
						fn (string $className) => new TraitUse($className),
						$reflection->getTraitNames()
					)
				),
			];
		}

		$traits = collect($traitsUses)
			->flatMap(function (TraitsUse $traitsUse) use ($reflection, $context) {
				$phpDoc = $this->phpDocStringParser->parse($traitsUse->docComment);

				return array_map(function (TraitUse $traitUse) use ($reflection, $context, $phpDoc) {
					$qualifiedName = $this->typeAliasResolver->resolve($traitUse->qualifiedName, $context->fileClassLikeContext);

					$tagValue = $phpDoc->firstUsesTagValue(
						fn (UsesTagValueNode $node) => $qualifiedName === $this->typeAliasResolver->resolve($node->type->type->name, $context->fileClassLikeContext)
					);

					[$type] = $this->map(
						$qualifiedName,
						$tagValue?->type,
						$context
					);

					Assert::notNull($type);
					ReflectionAssert::namedType($type, 'mapping trait `use` for [' . $reflection->getName() . ']');

					return [$type, $traitUse->aliases];
				}, $traitsUse->traits);
			})
			->map(fn (array $data) => new UsedTraitDefinition(
				trait: $data[0],
				aliases: array_map(fn (array $aliasData) => new UsedTraitAliasDefinition(
					name: $aliasData[0],
					newName: $aliasData[1],
					newModifier: $aliasData[2],
				), $data[1])
			))
			->all();

		return new UsedTraitsDefinition(
			traits: $traits,
			excludedTraitMethods: $context->fileClassLikeContext?->excludedTraitMethods ?? [],
		);
	}

	/**
	 * @param ReflectionEnum<UnitEnum> $reflection
	 *
	 * @return list<EnumCaseDefinition>
	 */
	private function enumCases(ReflectionEnum $reflection): array
	{
		return array_map(
			fn (ReflectionEnumUnitCase $case) => new EnumCaseDefinition(
				name: $case->getName(),
				backingValue: $case instanceof ReflectionEnumBackedCase ? $case->getBackingValue() : null,
			),
			$reflection->getCases()
		);
	}
}
