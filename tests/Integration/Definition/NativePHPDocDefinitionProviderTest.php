<?php

namespace Tests\Integration\Definition;

use DateTime;
use Generator;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\Native\NativeTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
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
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeConstantDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitAliasDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\ErrorType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use stdClass;
use Tests\Integration\IntegrationTestCase;
use Tests\Stubs\Classes\AllMissingTypes;
use Tests\Stubs\Classes\AllNativeTypes;
use Tests\Stubs\Classes\AllPhpDocTypes;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\ContravariantParameter;
use Tests\Stubs\Classes\CovariantParameter;
use Tests\Stubs\Classes\DoubleTemplateType;
use Tests\Stubs\Classes\InvariantParameter;
use Tests\Stubs\Classes\ParentClassStub;
use Tests\Stubs\Classes\PrefixedPhpDocTags;
use Tests\Stubs\Classes\SingleTemplateTypeImpl;
use Tests\Stubs\Classes\SomeStub;
use Tests\Stubs\Enums\BackedEnum;
use Tests\Stubs\Enums\UnitEnum;
use Tests\Stubs\Interfaces\NonGenericInterface;
use Tests\Stubs\Interfaces\ParentInterfaceStub;
use Tests\Stubs\Interfaces\SingleGenericInterface;
use Tests\Stubs\Interfaces\SingleTemplateType;
use Tests\Stubs\Traits\ParentTraitStub;
use Tests\Stubs\Traits\TraitWithoutProperties;

/**
 * @see NativePHPDocDefinitionProvider
 */
class NativePHPDocDefinitionProviderTest extends IntegrationTestCase
{
	private NativePHPDocDefinitionProvider $definitionProvider;

	protected function setUp(): void
	{
		parent::setUp();

		$parserConfig = new ParserConfig(usedAttributes: []);

		$this->definitionProvider = new NativePHPDocDefinitionProvider(
			new PhpDocStringParser(
				new Lexer($parserConfig),
				new PhpDocParser(
					$parserConfig,
					new TypeParser($parserConfig, $constExprParser = new ConstExprParser($parserConfig)),
					$constExprParser,
				),
			),
			new FileContextParser(
				new Php7(new Emulative()),
			),
			new TypeAliasResolver(),
			new NativeTypeMapper(),
			new PhpDocTypeMapper(
				new TypeAliasResolver()
			),
		);
	}

	public static function providesDefinitionForTypeProvider(): iterable
	{
		yield ClassStub::class => [
			ClassStub::class,
			new ClassTypeDefinition(
				qualifiedName: ClassStub::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/ClassStub.php'),
				builtIn: false,
				anonymous: false,
				final: true,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
					new TypeParameterDefinition(
						name: 'S',
						variadic: false,
						upperBound: PrimitiveType::integer(),
						variance: TemplateTypeVariance::COVARIANT,
					),
				],
				extends: new NamedType(ParentClassStub::class, [
					new TemplateType(
						name: 'T'
					),
					new NamedType(SomeStub::class),
				]),
				implements: [
					new NamedType(ParentInterfaceStub::class, [
						new TemplateType(
							name: 'S'
						),
						new NamedType(SomeStub::class),
					]),
				],
				uses: new UsedTraitsDefinition(
					traits: [
						new UsedTraitDefinition(
							trait: NamedType::wrap(ParentTraitStub::class, [
								new TemplateType('T'),
								SomeStub::class,
							]),
							aliases: [
								new UsedTraitAliasDefinition('traitMethod', null, ReflectionMethod::IS_PRIVATE),
								new UsedTraitAliasDefinition('traitMethod', 'traitMethodTwo', ReflectionMethod::IS_PROTECTED),
							]
						),
						new UsedTraitDefinition(
							trait: NamedType::wrap(ParentTraitStub::class)
						),
					]
				),
				constants: [],
				properties: [
					new PropertyDefinition(
						name: 'factories',
						type: PrimitiveType::array(new NamedType(SomeStub::class)),
						typeSource: TypeSource::PHP_DOC,
						hasDefaultValue: false,
						isPromoted: false,
					),
					new PropertyDefinition(
						name: 'generic',
						type: new NamedType(DoubleTemplateType::class, [
							new NamedType(DateTime::class),
							new TemplateType(
								name: 'T'
							),
						]),
						typeSource: TypeSource::PHP_DOC,
						hasDefaultValue: false,
						isPromoted: false,
					),
					new PropertyDefinition(
						name: 'promoted',
						type: new TemplateType('T'),
						typeSource: TypeSource::PHP_DOC,
						hasDefaultValue: false,
						isPromoted: true,
					),
				],
				methods: [
					new MethodDefinition(
						name: '__construct',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'promoted',
								type: new TemplateType('T'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: null,
						returnTypeSource: null,
					),
					new MethodDefinition(
						name: 'method',
						typeParameters: [
							new TypeParameterDefinition(
								name: 'G',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
						],
						parameters: [
							new FunctionParameterDefinition(
								name: 'param',
								type: new NamedType(DoubleTemplateType::class, [
									new NamedType(SomeStub::class),
									new TemplateType(
										name: 'T'
									),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: new NamedType(Collection::class, [
							new TemplateType(
								name: 'S'
							),
							new TemplateType(
								name: 'G'
							),
						]),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'methodTwo',
						typeParameters: [
							new TypeParameterDefinition(
								name: 'KValue',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
							new TypeParameterDefinition(
								name: 'K',
								variadic: false,
								upperBound: new NamedType(SingleTemplateType::class, [
									new TemplateType(
										name: 'KValue'
									),
								]),
								variance: TemplateTypeVariance::INVARIANT,
							),
						],
						parameters: [
							new FunctionParameterDefinition(
								name: 'param',
								type: new TemplateType(
									name: 'K'
								),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: new TemplateType(
							name: 'KValue'
						),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'self',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'parent',
								type: NamedType::wrap(ParentClassStub::class, [
									PrimitiveType::integer(),
									PrimitiveType::integer(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: new StaticType(
							new NamedType(ClassStub::class)
						),
						returnTypeSource: TypeSource::NATIVE,
					),
				],
			),
		];

		yield AllMissingTypes::class => [
			AllMissingTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllMissingTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllMissingTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [],
				extends: new NamedType(SomeStub::class),
				implements: [
					new NamedType(SingleTemplateType::class),
				],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [
					new PropertyDefinition(
						name: 'property',
						type: null,
						typeSource: null,
						hasDefaultValue: true,
						isPromoted: false,
					),
					new PropertyDefinition(
						name: 'promoted',
						type: null,
						typeSource: null,
						hasDefaultValue: true,
						isPromoted: true,
					),
					new PropertyDefinition(
						name: 'promotedDefault',
						type: new NullableType(PrimitiveType::integer()),
						typeSource: TypeSource::NATIVE,
						hasDefaultValue: false,
						isPromoted: true,
					),
				],
				methods: [
					new MethodDefinition(
						name: '__construct',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'promoted',
								type: null,
								typeSource: null,
								hasDefaultValue: true,
							),
							new FunctionParameterDefinition(
								name: 'promotedDefault',
								type: new NullableType(PrimitiveType::integer()),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: true,
							),
						],
						returnType: null,
						returnTypeSource: null,
					),
					new MethodDefinition(
						name: 'test',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'something',
								type: null,
								typeSource: null,
								hasDefaultValue: false,
							),
						],
						returnType: null,
						returnTypeSource: null,
					),
				],
			),
		];

		yield AllNativeTypes::class => [
			AllNativeTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllNativeTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllNativeTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [
					new MethodDefinition(
						name: 'f1',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::float(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array'),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::object(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('callable'),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new NamedType('iterable'),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: MixedType::get(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
						],
						returnType: VoidType::get(),
						returnTypeSource: TypeSource::NATIVE,
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NullableType(PrimitiveType::integer()),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new NullableType(
									new UnionType([
										PrimitiveType::string(),
										PrimitiveType::float(),
									])
								),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new UnionType([
									PrimitiveType::string(),
									PrimitiveType::float(),
								]),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new UnionType([
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								]),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType(AllNativeTypes::class),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
						],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::NATIVE,
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: [],
						parameters: [],
						returnType: new StaticType(
							new NamedType(AllNativeTypes::class)
						),
						returnTypeSource: TypeSource::NATIVE,
					),
				],
			),
		];

		yield AllPhpDocTypes::class => [
			AllPhpDocTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllPhpDocTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllPhpDocTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [
					new MethodDefinition(
						name: 'f1',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::integer(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new UnionType([
									PrimitiveType::integer(),
									PrimitiveType::float(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: new ErrorType('numeric'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p10',
								type: PrimitiveType::float(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p11',
								type: PrimitiveType::float(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: VoidType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: PrimitiveType::string(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f4',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NamedType('array', [
									new UnionType([
										PrimitiveType::integer(),
										PrimitiveType::string(),
									]),
									PrimitiveType::string(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new UnionType([
									PrimitiveType::integer(),
									PrimitiveType::string(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('array'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('array', [
									PrimitiveType::string(),
									PrimitiveType::string(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array', [
									PrimitiveType::integer(),
									PrimitiveType::string(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: new NamedType('array', [
									PrimitiveType::integer(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f5',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'p1',
								type: new UnionType([
									PrimitiveType::integer(),
									PrimitiveType::float(),
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								]),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new ErrorType('null'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('iterable'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('callable'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('resource'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: MixedType::get(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('object'),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f6',
						typeParameters: [],
						parameters: [],
						returnType: NeverType::get(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f7',
						typeParameters: [],
						parameters: [],
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f8',
						typeParameters: [],
						parameters: [],
						returnType: new NamedType(AllPhpDocTypes::class),
						returnTypeSource: TypeSource::PHP_DOC,
					),
					new MethodDefinition(
						name: 'f9',
						typeParameters: [],
						parameters: [],
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
						returnTypeSource: TypeSource::PHP_DOC,
					),
				],
			),
		];

		yield InvariantParameter::class => [
			InvariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: InvariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/InvariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
				],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [],
			),
		];

		yield ContravariantParameter::class => [
			ContravariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: ContravariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/ContravariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::CONTRAVARIANT,
					),
				],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [],
			),
		];

		yield CovariantParameter::class => [
			CovariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: CovariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/CovariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
				],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [],
			),
		];

		yield NonGenericInterface::class => [
			NonGenericInterface::class,
			new InterfaceTypeDefinition(
				qualifiedName: NonGenericInterface::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Interfaces/NonGenericInterface.php'),
				builtIn: false,
				typeParameters: [],
				extends: [],
				constants: [
					new TypeConstantDefinition(
						name: 'INTERFACE_CONSTANT',
						isFinal: true,
						type: null,
						typeSource: null,
					),
				],
				methods: [
					new MethodDefinition(
						name: 'function',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'i',
								type: PrimitiveType::string(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
						],
						returnType: MixedType::get(),
						returnTypeSource: TypeSource::NATIVE,
					),
				],
			),
		];

		yield [
			SingleTemplateType::class,
			new InterfaceTypeDefinition(
				qualifiedName: SingleTemplateType::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Interfaces/SingleTemplateType.php'),
				builtIn: false,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
				],
				extends: [],
				constants: [],
				methods: [],
			),
		];

		yield TraitWithoutProperties::class => [
			TraitWithoutProperties::class,
			new TraitTypeDefinition(
				qualifiedName: TraitWithoutProperties::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Traits/TraitWithoutProperties.php'),
				builtIn: false,
				typeParameters: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: [
					new MethodDefinition(
						name: 'otherFunction',
						typeParameters: [],
						parameters: [],
						returnType: new NamedType(Generator::class),
						returnTypeSource: TypeSource::NATIVE,
					),
				],
			),
		];

		yield BackedEnum::class => [
			BackedEnum::class,
			new EnumTypeDefinition(
				qualifiedName: BackedEnum::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Enums/BackedEnum.php'),
				builtIn: false,
				backingType: PrimitiveType::string(),
				implements: [
					new NamedType(SingleGenericInterface::class, [
						PrimitiveType::string(),
					]),
					new NamedType(\BackedEnum::class),
				],
				uses: new UsedTraitsDefinition(),
				cases: [
					new EnumCaseDefinition(
						name: 'FIRST',
						backingValue: 'first',
					),
					new EnumCaseDefinition(
						name: 'SECOND',
						backingValue: 'second',
					),
				],
				constants: [
					new TypeConstantDefinition(
						name: 'ALIASED',
						isFinal: false,
						type: null,
						typeSource: null,
					),
				],
				methods: [],
			),
		];

		yield UnitEnum::class => [
			UnitEnum::class,
			new EnumTypeDefinition(
				qualifiedName: UnitEnum::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Enums/UnitEnum.php'),
				builtIn: false,
				backingType: null,
				implements: [
					new NamedType(NonGenericInterface::class),
					new NamedType(\UnitEnum::class),
				],
				uses: new UsedTraitsDefinition(
					traits: [
						new UsedTraitDefinition(
							trait: NamedType::wrap(TraitWithoutProperties::class),
						),
						new UsedTraitDefinition(
							trait: NamedType::wrap(TraitWithoutProperties::class),
							aliases: [
								new UsedTraitAliasDefinition('otherFunction', 'otherOtherFunction'),
							]
						),
					]
				),
				cases: [
					new EnumCaseDefinition(
						name: 'FIRST',
						backingValue: null,
					),
					new EnumCaseDefinition(
						name: 'SECOND',
						backingValue: null,
					),
				],
				constants: [],
				methods: [
					new MethodDefinition(
						name: 'function',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'i',
								type: PrimitiveType::string(),
								typeSource: TypeSource::NATIVE,
								hasDefaultValue: false,
							),
						],
						returnType: MixedType::get(),
						returnTypeSource: TypeSource::NATIVE,
					),
				],
			),
		];

		yield PrefixedPhpDocTags::class => [
			PrefixedPhpDocTags::class,
			new ClassTypeDefinition(
				qualifiedName: PrefixedPhpDocTags::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/PrefixedPhpDocTags.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [
					new TypeParameterDefinition(
						name: 'TPhpStan',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
					new TypeParameterDefinition(
						name: 'TPsalmCovariant',
						variadic: false,
						upperBound: PrimitiveType::integer(),
						variance: TemplateTypeVariance::COVARIANT,
					),
				],
				extends: new NamedType(SingleTemplateTypeImpl::class, [
					PrimitiveType::integer(),
				]),
				implements: [
					new NamedType(SingleGenericInterface::class, [
						PrimitiveType::string(),
					]),
				],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [
					new PropertyDefinition(
						name: 'property',
						type: new NamedType(stdClass::class),
						typeSource: TypeSource::PHP_DOC,
						hasDefaultValue: false,
						isPromoted: false,
					),
				],
				methods: [
					new MethodDefinition(
						name: 'method',
						typeParameters: [],
						parameters: [
							new FunctionParameterDefinition(
								name: 'param1',
								type: PrimitiveType::boolean(),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
							new FunctionParameterDefinition(
								name: 'param2',
								type: new NamedType(stdClass::class),
								typeSource: TypeSource::PHP_DOC,
								hasDefaultValue: false,
							),
						],
						returnType: PrimitiveType::integer(),
						returnTypeSource: TypeSource::PHP_DOC,
					),
				],
			),
		];
	}

	#[DataProvider('providesDefinitionForTypeProvider')]
	public function testProvidesDefinitionForType(string $type, TypeDefinition $expected): void
	{
		$actual = $this->definitionProvider->forType($type);

		self::assertEquals($expected, $actual);
	}
}
