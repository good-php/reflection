<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc;

use Exception;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\TypeContext;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
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
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Webmozart\Assert\Assert;

class PhpDocTypeMapper
{
	public function __construct(
		private readonly TypeAliasResolver $typeAliasResolver,
	) {}

	/**
	 * @param TypeNode|iterable<int, TypeNode> $node
	 *
	 * @return ($node is TypeNode ? Type : Collection<int, Type>)
	 */
	public function map(TypeNode|iterable $node, TypeContext $context): Type|Collection
	{
		if (!$node instanceof TypeNode) {
			return Collection::wrap($node)->map(fn (TypeNode $node) => $this->map($node, $context));
		}

		try {
			return match (true) {
				$node instanceof ArrayTypeNode => PrimitiveType::array(
					$this->map($node->type, $context)
				),
				$node instanceof ArrayShapeNode => new TupleType(
					collect($node->items)->map(fn (ArrayShapeItemNode $node) => $this->map($node->valueType, $context))
				),
				$node instanceof CallableTypeNode => $this->mapNamed(
					$node->identifier->name,
					new Collection([
						$this->map($node->returnType, $context),
						...array_map(
							fn (CallableTypeParameterNode $parameterNode) => $this->map($parameterNode->type, $context),
							$node->parameters
						),
					]),
					$context,
				),
				$node instanceof GenericTypeNode => $this->mapNamed(
					$node->type->name,
					$this->map($node->genericTypes, $context),
					$context,
				),
				$node instanceof IdentifierTypeNode => $this->mapNamed(
					$node->name,
					new Collection(),
					$context,
				),
				$node instanceof IntersectionTypeNode => new IntersectionType(
					$this->map($node->types, $context)
				),
				$node instanceof NullableTypeNode => new NullableType(
					$this->map($node->type, $context),
				),
				$node instanceof ThisTypeNode => new StaticType(
					$context->declaringType,
				),
				$node instanceof UnionTypeNode => $this->mapUnion($node, $context),
				default                        => new ErrorType((string) $node),
			};
		} catch (Exception) {
			return new ErrorType((string) $node);
		}
	}

	/**
	 * @param Collection<int, Type> $arguments
	 */
	public function mapNamed(string $type, Collection $arguments, TypeContext $context): Type
	{
		if ($context->typeParameters[$type] ?? null) {
			return new TemplateType(
				name: $type,
			);
		}

		$comparisonType = $this->typeAliasResolver->forComparison($type);

		if ($comparisonType === 'parent') {
			Assert::notNull($context->declaringTypeParent, 'Used [parent] type without a parent class.');

			return new NamedType($context->declaringTypeParent->name, $arguments);
		}

		if ($comparisonType === 'numeric' || $comparisonType === 'null') {
			return new ErrorType($type);
		}

		$specialType = match ($comparisonType) {
			'mixed' => MixedType::get(),
			'never', 'never-return', 'never-returns', 'no-return', 'noreturn' => NeverType::get(),
			'void' => VoidType::get(),
			'int', 'integer', 'positive-int', 'negative-int', 'int-mask', 'int-mask-of' => PrimitiveType::integer(),
			'number' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			])),
			'float', 'double' => PrimitiveType::float(),
			'string', 'numeric-string', 'literal-string', 'class-string',
			'interface-string', 'trait-string', 'callable-string', 'non-empty-string' => PrimitiveType::string(),
			'bool', 'boolean', 'true', 'false' => PrimitiveType::boolean(),
			'array-key' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::string(),
			])),
			'callable', 'iterable', 'resource', 'object' => new NamedType($type, $arguments),
			'array' => match ($arguments->count()) {
				1       => PrimitiveType::array($arguments[0]),
				default => new NamedType('array', $arguments)
			},
			'associative-array', 'non-empty-array', 'list', 'non-empty-list' => new NamedType('array', $arguments),
			'scalar' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
				PrimitiveType::string(),
				PrimitiveType::boolean(),
			])),
			'self'   => new NamedType($context->declaringType->name, $arguments),
			'static' => new StaticType($context->declaringType),
			default  => null,
		};

		if ($specialType) {
			return $specialType;
		}

		$type = $this->typeAliasResolver->resolve($type, $context->fileClassLikeContext);

		return new NamedType($type, $arguments);
	}

	private function mapUnion(UnionTypeNode $node, TypeContext $context): Type
	{
		$isNullNode = fn (TypeNode $node) => $node instanceof IdentifierTypeNode && $node->name === 'null';
		$types = $node->types;
		$containsNull = false;

		if (Arr::first($types, $isNullNode)) {
			$types = array_values(
				array_filter($types, fn (TypeNode $node) => !$isNullNode($node))
			);
			$containsNull = true;
		}

		$mappedType = count($types) >= 2 ?
			new UnionType(
				$this->map($types, $context)
			) :
			$this->map($types[0], $context);

		return $containsNull ? new NullableType($mappedType) : $mappedType;
	}
}
