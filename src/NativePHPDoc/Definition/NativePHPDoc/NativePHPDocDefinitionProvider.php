<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc;

use BackedEnum;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\Native\NativeTypeMapper;
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
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Util\LateInitLazy;
use GoodPhp\Reflection\Util\ReflectionAssert;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
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
use TenantCloud\Standard\Lazy\Lazy;
use UnitEnum;
use Webmozart\Assert\Assert;

use function TenantCloud\Standard\Lazy\lazy;

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

	public function map(
		ReflectionType|string|null $nativeType,
		?TypeNode $phpDocType,
		TypeContext $context
	): ?Type {
		if (!$nativeType && !$phpDocType) {
			return null;
		}

		return $phpDocType ?
			$this->phpDocTypeMapper->map($phpDocType, $context) :
			$this->nativeTypeMapper->map($nativeType, $context);
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
	 * @param class-string<object> $type
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
			implements: $this->interfaces($reflection, $phpDoc, $context)->concat($implicitInterfaces),
			uses: $this->traits($reflection, $context),
			cases: $this->enumCases($reflection),
			methods: $this->methods($reflection, $context),
		);
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function createTypeContext(ReflectionClass $reflection, PhpDocNode $phpDoc): TypeContext
	{
		$context = new TypeContext(
			fileClassLikeContext: $this->fileContextParser
				->parse($reflection)
				?->forClassLike($reflection),
			declaringType: new NamedType($reflection->getName()),
			declaringTypeParent: $reflection->getParentClass() ? new NamedType($reflection->getParentClass()->getName()) : null,
			typeParameters: new Collection()
		);

		$lazyTypeParameters = $this->lazyTypeParameters($phpDoc, $context);

		return $context->withMergedTypeParameters($lazyTypeParameters);
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return ($reflection is ReflectionEnum ? class-string<UnitEnum> : class-string<object>)
	 */
	private function qualifiedName(ReflectionClass $reflection): string
	{
		return $reflection->getName();
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function fileName(ReflectionClass $reflection): ?string
	{
		return $reflection->getFileName() ?: null;
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return Collection<int, PropertyDefinition>
	 */
	private function properties(ReflectionClass $reflection, TypeContext $context): Collection
	{
		$constructorPhpDoc = $this->phpDocStringParser->parse(
			$reflection->getConstructor()?->getDocComment() ?: ''
		);

		return Collection::make($reflection->getProperties())
			->filter(fn (ReflectionProperty $property) => !$context->fileClassLikeContext || $context->fileClassLikeContext->declaredProperties->contains($property->getName()))
			->map(function (ReflectionProperty $property) use ($context, $constructorPhpDoc) {
				$phpDoc = $this->phpDocStringParser->parse($property);

				// Get first @var tag (if any specified). Works for both regular and promoted properties.
				/** @var TypeNode|null $phpDocType */
				$phpDocType = $phpDoc->getVarTagValues()[0]->type ?? null;

				// If none found, fallback to @param tag if it's a promoted property. The check for promoted property
				// is important because there could be a property with the same name as a parameter, but those being unrelated.
				if (!$phpDocType && $property->isPromoted()) {
					/** @var ParamTagValueNode|null $paramNode */
					$paramNode = Arr::first(
						$constructorPhpDoc->getParamTagValues(),
						fn (ParamTagValueNode $node) => Str::after($node->parameterName, '$') === $property->getName()
					);

					$phpDocType = $paramNode?->type;
				}

				return new PropertyDefinition(
					name: $property->getName(),
					type: $this->map(
						$property->getType(),
						$phpDocType,
						$context
					),
					hasDefaultValue: $property->hasDefaultValue(),
					isPromoted: $property->isPromoted(),
				);
			});
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return Collection<int, MethodDefinition>
	 */
	private function methods(ReflectionClass $reflection, TypeContext $context): Collection
	{
		return Collection::make($reflection->getMethods())
			->filter(fn (ReflectionMethod $method) => !$context->fileClassLikeContext || $context->fileClassLikeContext->declaredMethods->contains($method->getName()))
			->map(function (ReflectionMethod $method) use ($context) {
				$phpDoc = $this->phpDocStringParser->parse($method);

				// Get first @return tag (if any specified).
				$phpDocType = $phpDoc->getReturnTagValues()[0]->type ?? null;
				$context = $context->withMergedTypeParameters(
					$this->lazyTypeParameters($phpDoc, $context)
				);

				return new MethodDefinition(
					name: $method->getName(),
					typeParameters: $this->typeParameters($phpDoc, $context),
					parameters: $this->functionParameters($method, $phpDoc, $context),
					returnType: $this->map(
						$method->getReturnType(),
						$phpDocType,
						$context,
					)
				);
			});
	}

	/**
	 * @return Collection<string, Lazy<TypeParameterDefinition>>
	 */
	private function lazyTypeParameters(PhpDocNode $phpDoc, TypeContext $context): Collection
	{
		/** @var Collection<string, Lazy<TypeParameterDefinition>> $lazyTypeParametersMap */
		$lazyTypeParametersMap = new Collection();
		/** @var LateInitLazy<TypeContext> $temporaryContext */
		$temporaryContext = new LateInitLazy();

		// For whatever reason phpstan/phpdoc-parser doesn't parse the differences between @template and @template-covariant,
		// so instead of using ->getTemplateTagValues() we'll filter tags manually.
		foreach ($phpDoc->getTags() as $node) {
			if (!$node->value instanceof TemplateTagValueNode) {
				continue;
			}

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
						}
					);
				}
			);
		}

		$temporaryContext->initialize($context->withMergedTypeParameters($lazyTypeParametersMap));

		return $lazyTypeParametersMap;
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	private function typeParameters(PhpDocNode $phpDoc, TypeContext $context): Collection
	{
		// The types are properly defined, but for whatever reason ->map() breaks it all.
		/** @var Collection<int, TypeParameterDefinition> */
		return $this->lazyTypeParameters($phpDoc, $context)
			->values()
			->map(fn (Lazy $lazy) => $lazy->value());
	}

	/**
	 * @return Collection<int, FunctionParameterDefinition>
	 */
	private function functionParameters(ReflectionMethod $reflection, PhpDocNode $phpDoc, TypeContext $context): Collection
	{
		return Collection::make($reflection->getParameters())
			->map(function (ReflectionParameter $parameter) use ($context, $phpDoc) {
				/** @var ParamTagValueNode|null $phpDocType */
				$phpDocType = Arr::first(
					$phpDoc->getParamTagValues(),
					fn (ParamTagValueNode $node) => Str::after($node->parameterName, '$') === $parameter->getName()
				);

				return new FunctionParameterDefinition(
					name: $parameter->getName(),
					type: $this->map(
						$parameter->getType(),
						$phpDocType?->type,
						$context
					),
					hasDefaultValue: $parameter->isDefaultValueAvailable(),
				);
			});
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function parent(ReflectionClass $reflection, PhpDocNode $phpDoc, TypeContext $context): ?NamedType
	{
		$parentClass = $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null;

		if (!$parentClass) {
			return null;
		}

		/** @var PhpDocTagNode|null $tag */
		$tag = Arr::first(
			$phpDoc->getTags(),
			fn (PhpDocTagNode $node) => $node->value instanceof ExtendsTagValueNode &&
				$parentClass === $this->typeAliasResolver->resolve($node->value->type->type->name, $context->fileClassLikeContext)
		);

		/** @var ExtendsTagValueNode|null $tagValue */
		$tagValue = $tag?->value;

		$type = $this->map(
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
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return Collection<int, NamedType>
	 */
	private function interfaces(ReflectionClass $reflection, PhpDocNode $phpDoc, TypeContext $context): Collection
	{
		return Collection::make($reflection->getInterfaceNames())
			->filter(fn (string $className) => !$context->fileClassLikeContext || $context->fileClassLikeContext->implementsInterfaces->contains($className))
			->map(function (string $className) use ($reflection, $context, $phpDoc) {
				/** @var PhpDocTagNode|null $tag */
				$tag = Arr::first(
					$phpDoc->getTags(),
					fn (PhpDocTagNode $node) => ($node->value instanceof ImplementsTagValueNode || $node->value instanceof ExtendsTagValueNode) &&
						$className === $this->typeAliasResolver->resolve($node->value->type->type->name, $context->fileClassLikeContext)
				);

				/** @var ImplementsTagValueNode|ExtendsTagValueNode|null $tagValue */
				$tagValue = $tag?->value;

				$type = $this->map(
					$className,
					$tagValue?->type,
					$context
				);

				Assert::notNull($type);
				ReflectionAssert::namedType($type, 'mapping `implements` types for [' . $reflection->getName() . ']');

				return $type;
			});
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function traits(ReflectionClass $reflection, TypeContext $context): UsedTraitsDefinition
	{
		if ($context->fileClassLikeContext) {
			$traitsUses = $context
				->fileClassLikeContext
				->traitsUses;
		} else {
			$traitsUses = collect([
				new TraitsUse(
					collect($reflection->getTraitNames())->map(fn (string $className) => new TraitUse($className))
				),
			]);
		}

		$traits = $traitsUses
			->flatMap(function (TraitsUse $traitsUse) use ($reflection, $context) {
				$phpDoc = $this->phpDocStringParser->parse($traitsUse->docComment);

				return $traitsUse
					->traits
					->map(function (TraitUse $traitUse) use ($reflection, $context, $phpDoc) {
						$qualifiedName = $this->typeAliasResolver->resolve($traitUse->qualifiedName, $context->fileClassLikeContext);
						/** @var PhpDocTagNode|null $tag */
						$tag = Arr::first(
							$phpDoc->getTags(),
							fn (PhpDocTagNode $node) => $node->value instanceof UsesTagValueNode &&
								$qualifiedName === $this->typeAliasResolver->resolve($node->value->type->type->name, $context->fileClassLikeContext)
						);

						/** @var UsesTagValueNode|null $tagValue */
						$tagValue = $tag?->value;

						$type = $this->map(
							$qualifiedName,
							$tagValue?->type,
							$context
						);

						Assert::notNull($type);
						ReflectionAssert::namedType($type, 'mapping trait `use` for [' . $reflection->getName() . ']');

						return [$type, $traitUse->aliases];
					});
			})
			->map(fn (array $data) => new UsedTraitDefinition(
				trait: $data[0],
				aliases: $data[1]->map(fn (array $aliasData) => new UsedTraitAliasDefinition(
					name: $aliasData[0],
					newName: $aliasData[1],
					newModifier: $aliasData[2],
				))
			));

		return new UsedTraitsDefinition(
			traits: $traits,
			excludedTraitMethods: $context->fileClassLikeContext?->excludedTraitMethods ?? collect(),
		);
	}

	/**
	 * @return Collection<int, EnumCaseDefinition>
	 */
	private function enumCases(ReflectionEnum $reflection): Collection
	{
		return Collection::make($reflection->getCases())
			->map(fn (ReflectionEnumUnitCase $case) => new EnumCaseDefinition(
				name: $case->getName(),
				backingValue: $case instanceof ReflectionEnumBackedCase ? $case->getBackingValue() : null,
			));
	}
}
