<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use Illuminate\Support\Collection;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PHPUnit\Framework\Assert;
use ReflectionProperty;

class ClassLikeContextParsingVisitor extends NodeVisitorAbstract
{
	/** @var Collection<string, FileClassLikeContext> */
	public Collection $classLikes;

	/** @var Collection<string, FileClassLikeContext> */
	public Collection $anonymousClassLikes;

	public function __construct(
		private readonly NameResolver $nameResolverVisitor,
	) {
		$this->classLikes = new Collection();
		$this->anonymousClassLikes = new Collection();
	}

	public function enterNode(Node $node)
	{
		if (
			!$node instanceof Node\Stmt\Class_ &&
			!$node instanceof Node\Stmt\Interface_ &&
			!$node instanceof Node\Stmt\Trait_ &&
			!$node instanceof Node\Stmt\Enum_
		) {
			return null;
		}

		$nameContext = $this->nameResolverVisitor->getNameContext();

		$context = new FileClassLikeContext(
			namespace: $nameContext->getNamespace() ? (string) $nameContext->getNamespace() : null,
			uses: $this->uses($nameContext),
			traitUses: $this->traitUses($node),
			declaredProperties: $this->properties($node),
			declaredMethods: $this->methods($node),
		);

		if ($node->name) {
			$this->classLikes[(string) $node->namespacedName] = $context;
		} else {
			Assert::assertArrayNotHasKey(
				$node->getStartLine(),
				$this->anonymousClassLikes,
				'Only one anonymous class-like can be defined within a single line due to PHPs limitations.'
			);

			$this->anonymousClassLikes[$node->getStartLine()] = $context;
		}

		return null;
	}

	private function uses(NameContext $nameContext): Collection
	{
		static $aliasesProperty;

		if (!$aliasesProperty) {
			$aliasesProperty = new ReflectionProperty(NameContext::class, 'aliases');
		}

		$uses = $aliasesProperty->getValue($nameContext)[Node\Stmt\Use_::TYPE_NORMAL] ?? [];

		return collect($uses);
	}

	private function traitUses(ClassLike $classLike): Collection
	{
		if (!$classLike instanceof Class_ && !$classLike instanceof Trait_) {
			return collect();
		}

		return collect($classLike->stmts)
			->filter(fn (Node $node) => $node instanceof TraitUse)
			->flatMap(function (TraitUse $node) {
				return array_map(
					function (Node\Name $traitName) use ($node) {
						$docComment = $node->getDocComment();

						return new FileClassLikeContext\TraitUse(
							name: (string) $traitName,
							docComment: $docComment ? (string) $docComment : null,
							aliases: []
						);
					},
					$node->traits,
				);
			});
	}

	private function properties(ClassLike $classLike): Collection
	{
		if (!$classLike instanceof Class_ && !$classLike instanceof Trait_) {
			return collect();
		}

		$properties = collect($classLike->stmts)
			->filter(fn (Node $node) => $node instanceof Property)
			->flatMap(fn (Property $node) => $node->props)
			->map(fn (PropertyProperty $property) => (string) $property->name);

		$constructor = collect($classLike->stmts)->first(fn (Node $node) => $node instanceof ClassMethod && (string) $node->name === '__construct');

		if (!$constructor) {
			return $properties;
		}

		$promoted = collect($constructor->params)
			->filter(fn (Node\Param $node) => $node->flags & Class_::VISIBILITY_MODIFIER_MASK)
			->map(fn (Node\Param $node) => (string) $node->var->name);

		return $properties->concat($promoted);
	}

	private function methods(ClassLike $classLike): Collection
	{
		return collect($classLike->stmts)
			->filter(fn (Node $node) => $node instanceof ClassMethod)
			->map(fn (ClassMethod $method) => (string) $method->name);
	}
}
