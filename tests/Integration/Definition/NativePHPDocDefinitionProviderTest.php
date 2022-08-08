<?php

namespace Tests\Integration\Definition;

use Generator;
use GoodPhp\Reflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use GoodPhp\Reflection\Definition\TypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\EnumCaseDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\ErrorType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\ParentType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use Tests\Integration\TestCase;
use Tests\Stubs\Classes\AllMissingTypes;
use Tests\Stubs\Classes\AllNativeTypes;
use Tests\Stubs\Classes\AllPhpDocTypes;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\ContravariantParameter;
use Tests\Stubs\Classes\CovariantParameter;
use Tests\Stubs\Classes\DoubleTemplateType;
use Tests\Stubs\Classes\InvariantParameter;
use Tests\Stubs\Classes\ParentClassStub;
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
class NativePHPDocDefinitionProviderTest extends TestCase
{
	private NativePHPDocDefinitionProvider $definitionProvider;

	protected function setUp(): void
	{
		parent::setUp();

		$this->definitionProvider = $this->container->get(NativePHPDocDefinitionProvider::class);
	}

	public static function providesDefinitionForTypeProvider(): Generator
	{
		yield [
			ClassStub::class,
			new ClassTypeDefinition(
				qualifiedName: ClassStub::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/ClassStub.php'),
				builtIn: false,
				anonymous: false,
				final: true,
				abstract: false,
				typeParameters: new Collection([
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
				]),
				extends: new NamedType(ParentClassStub::class, collect([
					new TemplateType(
						name: 'T'
					),
					new NamedType(SomeStub::class),
				])),
				implements: new Collection([
					new NamedType(ParentInterfaceStub::class, collect([
						new TemplateType(
							name: 'T'
						),
						new NamedType(SomeStub::class),
					])),
				]),
				uses: new Collection([
					new NamedType(ParentTraitStub::class),
				]),
				properties: new Collection([
					new PropertyDefinition(
						name: 'factories',
						type: PrimitiveType::array(new NamedType(SomeStub::class)),
					),
					new PropertyDefinition(
						name: 'generic',
						type: new NamedType(DoubleTemplateType::class, collect([
							new NamedType(SomeStub::class),
							new TemplateType(
								name: 'T'
							),
						]))
					),
					new PropertyDefinition(
						name: 'promoted',
						type: new TemplateType('T'),
					),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: '__construct',
						typeParameters: collect(),
						parameters: collect([
							new FunctionParameterDefinition(
								name: 'promoted',
								type: new TemplateType('T'),
							),
						]),
						returnType: null,
					),
					new MethodDefinition(
						name: 'method',
						typeParameters: collect([
							new TypeParameterDefinition(
								name: 'G',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
						]),
						parameters: collect([
							new FunctionParameterDefinition(
								name: 'param',
								type: new NamedType(DoubleTemplateType::class, collect([
									new NamedType(SomeStub::class),
									new TemplateType(
										name: 'T'
									),
								]))
							),
						]),
						returnType: new NamedType(Collection::class, collect([
							new TemplateType(
								name: 'S'
							),
							new TemplateType(
								name: 'G'
							),
						]))
					),
					new MethodDefinition(
						name: 'methodTwo',
						typeParameters: collect([
							new TypeParameterDefinition(
								name: 'KValue',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
							new TypeParameterDefinition(
								name: 'K',
								variadic: false,
								upperBound: new NamedType(SingleTemplateType::class, collect([
									new TemplateType(
										name: 'KValue'
									),
								])),
								variance: TemplateTypeVariance::INVARIANT,
							),
						]),
						parameters: collect([
							new FunctionParameterDefinition(
								name: 'param',
								type: new TemplateType(
									name: 'K'
								),
							),
						]),
						returnType: new TemplateType(
							name: 'KValue'
						)
					),
					new MethodDefinition(
						name: 'self',
						typeParameters: collect([]),
						parameters: collect([]),
						returnType: new StaticType(
							new NamedType(ClassStub::class)
						)
					),
					new MethodDefinition(
						name: 'par',
						typeParameters: collect([]),
						parameters: collect([]),
						returnType: new ParentType(
							new NamedType(ClassStub::class)
						)
					),
				]),
			),
		];

		yield [
			AllMissingTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllMissingTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllMissingTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: new NamedType(SomeStub::class),
				implements: new Collection([
					new NamedType(SingleTemplateType::class),
				]),
				uses: new Collection(),
				properties: new Collection([
					new PropertyDefinition(
						name: 'property',
						type: null,
					),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: 'test',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'something',
								type: null
							),
						]),
						returnType: null
					),
				]),
			),
		];

		yield [
			AllNativeTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllNativeTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllNativeTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'f1',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::float(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::object(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('callable')
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new NamedType('iterable'),
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: MixedType::get(),
							),
						]),
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NullableType(PrimitiveType::integer()),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new NullableType(
									new UnionType(new Collection([
										PrimitiveType::string(),
										PrimitiveType::float(),
									]))
								),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new UnionType(new Collection([
									PrimitiveType::string(),
									PrimitiveType::float(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new UnionType(new Collection([
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType(AllNativeTypes::class),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllNativeTypes::class)
						),
					),
				]),
			),
		];

		yield [
			AllPhpDocTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllPhpDocTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllPhpDocTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'f1',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::float(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: new ErrorType('numeric'),
							),
							new FunctionParameterDefinition(
								name: 'p10',
								type: PrimitiveType::float(),
							),
							new FunctionParameterDefinition(
								name: 'p11',
								type: PrimitiveType::float(),
							),
						]),
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: PrimitiveType::string(),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f4',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::string(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: new NamedType('array'),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f5',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::float(),
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new ErrorType('null'),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('iterable'),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('callable'),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('resource'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: MixedType::get(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('object'),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f6',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f7',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
					),
					new MethodDefinition(
						name: 'f8',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new NamedType(AllPhpDocTypes::class),
					),
					new MethodDefinition(
						name: 'f9',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
					),
				]),
			),
		];

		yield [
			InvariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: InvariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/InvariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
				]),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection(),
			),
		];

		yield [
			ContravariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: ContravariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/ContravariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::CONTRAVARIANT,
					),
				]),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection(),
			),
		];

		yield [
			CovariantParameter::class,
			new ClassTypeDefinition(
				qualifiedName: CovariantParameter::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/CovariantParameter.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
				]),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection(),
			),
		];

		yield [
			NonGenericInterface::class,
			new InterfaceTypeDefinition(
				qualifiedName: NonGenericInterface::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Interfaces/NonGenericInterface.php'),
				builtIn: false,
				typeParameters: new Collection(),
				extends: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'function',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'i',
								type: PrimitiveType::string(),
							),
						]),
						returnType: MixedType::get(),
					),
				]),
			),
		];

		yield [
			SingleTemplateType::class,
			new InterfaceTypeDefinition(
				qualifiedName: SingleTemplateType::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Interfaces/SingleTemplateType.php'),
				builtIn: false,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
				]),
				extends: new Collection(),
				methods: new Collection(),
			),
		];

		yield [
			TraitWithoutProperties::class,
			new TraitTypeDefinition(
				qualifiedName: TraitWithoutProperties::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Traits/TraitWithoutProperties.php'),
				builtIn: false,
				typeParameters: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'otherFunction',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new NamedType(Generator::class),
					),
				]),
			),
		];

		yield [
			BackedEnum::class,
			new EnumTypeDefinition(
				qualifiedName: BackedEnum::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Enums/BackedEnum.php'),
				builtIn: false,
				backingType: PrimitiveType::string(),
				implements: new Collection([
					new NamedType(SingleGenericInterface::class, new Collection([
						PrimitiveType::string(),
					])),
					new NamedType(\UnitEnum::class),
					new NamedType(\BackedEnum::class),
				]),
				uses: new Collection(),
				cases: new Collection([
					new EnumCaseDefinition(
						name: 'FIRST',
						backingValue: 'first',
					),
					new EnumCaseDefinition(
						name: 'SECOND',
						backingValue: 'second',
					),
				]),
				methods: new Collection(),
			),
		];

		yield [
			UnitEnum::class,
			new EnumTypeDefinition(
				qualifiedName: UnitEnum::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Enums/UnitEnum.php'),
				builtIn: false,
				backingType: null,
				implements: new Collection([
					new NamedType(NonGenericInterface::class),
					new NamedType(\UnitEnum::class),
				]),
				uses: new Collection([
					new NamedType(TraitWithoutProperties::class),
					new NamedType(TraitWithoutProperties::class),
				]),
				cases: new Collection([
					new EnumCaseDefinition(
						name: 'FIRST',
						backingValue: null,
					),
					new EnumCaseDefinition(
						name: 'SECOND',
						backingValue: null,
					),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: 'function',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'i',
								type: PrimitiveType::string(),
							),
						]),
						returnType: MixedType::get(),
					),
				]),
			),
		];
	}

	/**
	 * @dataProvider providesDefinitionForTypeProvider
	 */
	public function testProvidesDefinitionForType(string $type, TypeDefinition $expected): void
	{
		$actual = $this->definitionProvider->forType($type);

		self::assertEquals($expected, $actual);
	}
}
