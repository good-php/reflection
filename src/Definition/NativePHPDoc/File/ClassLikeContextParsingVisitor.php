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
use ReflectionProperty;
use Webmozart\Assert\Assert;

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
			Assert::keyNotExists(
				$this->anonymousClassLikes->all(),
				$node->getStartLine(),
				'Only one anonymous class-like can be defined within a single line due to PHPs limitations.'
			);

			$this->anonymousClassLikes[$node->getStartLine()] = $context;
		}

		return null;
	}

	/**
	 * @return Collection<string, string>
	 */
	private function uses(NameContext $nameContext): Collection
	{
		static $aliasesProperty;

		if (!$aliasesProperty) {
			$aliasesProperty = new ReflectionProperty(NameContext::class, 'aliases');
		}

		/** @var array<string, Node\Name> $uses */
		$uses = $aliasesProperty->getValue($nameContext)[Node\Stmt\Use_::TYPE_NORMAL] ?? [];

		return collect($uses)
			->map(fn (Node\Name $name) => (string) $name);
	}

	/**
	 * @return Collection<int, FileClassLikeContext\TraitUse>
	 */
	private function traitUses(ClassLike $classLike): Collection
	{
		if (!$classLike instanceof Class_ && !$classLike instanceof Trait_) {
			return collect();
		}

		return collect($classLike->stmts)
			->whereInstanceOf(TraitUse::class)
			->flatMap(fn (TraitUse $node) => array_map(
				fn (Node\Name $traitName) => new FileClassLikeContext\TraitUse(
					name: (string) $traitName,
					docComment: (string) $node->getDocComment() ?: null,
					aliases: []
				),
				$node->traits,
			));
	}

	/**
	 * @return Collection<int, string>
	 */
	private function properties(ClassLike $classLike): Collection
	{
		if (!$classLike instanceof Class_ && !$classLike instanceof Trait_) {
			return collect();
		}

		$properties = collect($classLike->stmts)
			->whereInstanceOf(Property::class)
			->flatMap(fn (Property $node) => $node->props)
			->map(fn (PropertyProperty $property) => (string) $property->name);

		/** @var ClassMethod|null $constructor */
		$constructor = collect($classLike->stmts)
			->first(fn (Node $node) => $node instanceof ClassMethod && (string) $node->name === '__construct');

		if (!$constructor) {
			return $properties;
		}

		$promoted = collect($constructor->params)
			->filter(fn (Node\Param $node) => (bool) ($node->flags & Class_::VISIBILITY_MODIFIER_MASK))
			->map(function (Node\Param $node): string {
				Assert::isInstanceOf($node->var, Node\Expr\Variable::class);
				Assert::string($node->var->name);

				return $node->var->name;
			});

		return $properties->concat($promoted);
	}

	/**
	 * @return Collection<int, string>
	 */
	private function methods(ClassLike $classLike): Collection
	{
		return collect($classLike->stmts)
			->whereInstanceOf(ClassMethod::class)
			->map(fn (ClassMethod $method) => (string) $method->name);
	}
}
