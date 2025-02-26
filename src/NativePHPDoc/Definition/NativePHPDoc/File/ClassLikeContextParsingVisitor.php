<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitUse;
use Illuminate\Support\Collection;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
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
			!$node instanceof Interface_ &&
			!$node instanceof Node\Stmt\Trait_ &&
			!$node instanceof Enum_
		) {
			return null;
		}

		$nameContext = $this->nameResolverVisitor->getNameContext();

		$context = new FileClassLikeContext(
			namespace: $nameContext->getNamespace() ? (string) $nameContext->getNamespace() : null,
			implementsInterfaces: $this->implementsInterfaces($node),
			uses: $this->uses($nameContext),
			traitsUses: $this->traitsUses($node),
			excludedTraitMethods: $this->excludedTraitMethods($node),
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
	 * @return Collection<class-string, Collection<int, string>>
	 */
	public function excludedTraitMethods(ClassLike $classLike): Collection
	{
		return collect($classLike->getTraitUses())
			->flatMap(
				fn (Node\Stmt\TraitUse $traitUseNode) => collect($traitUseNode->adaptations)->whereInstanceOf(Precedence::class)
			)
			->reduce(function (Collection $carry, Precedence $precedence) {
				foreach ($precedence->insteadof as $insteadof) {
					/** @var Collection<string, Collection<int, string>> $carry */
					$carry[(string) $insteadof] ??= collect();
					$carry[(string) $insteadof]->push((string) $precedence->method);
				}

				return $carry;
			}, collect());
	}

	/**
	 * @return Collection<int, string>
	 */
	private function implementsInterfaces(ClassLike $classLike): Collection
	{
		$nameNodes = match (true) {
			$classLike instanceof Class_     => $classLike->implements,
			$classLike instanceof Interface_ => $classLike->extends,
			$classLike instanceof Enum_      => $classLike->implements,
			default                          => [],
		};

		return collect($nameNodes)
			->map(fn (Name $name) => (string) $name);
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

		/** @var array<string, Name> $uses */
		/** @phpstan-ignore-next-line */
		$uses = $aliasesProperty->getValue($nameContext)[Node\Stmt\Use_::TYPE_NORMAL] ?? [];

		return collect($uses)
			->map(fn (Name $name) => (string) $name);
	}

	/**
	 * @return Collection<int, TraitsUse>
	 */
	private function traitsUses(ClassLike $classLike): Collection
	{
		// You don't wanna know how much time I spent researching how these stupid traits work in PHP.
		// What's crazy here is that even Roave/BetterReflection currently gets it wrong and doesn't
		// work the same way traits actually work in PHP. It's so unnecessarily overcomplicated.
		//
		// Not only can you use the same trait multiple times, you can also create infinite copies of a method
		// by aliases (which doesn't actually remove the "original" method, just adds a new one on top)
		// and even remove methods from used traits with "precedence".
		// After __halt_compiler, this is definitely the most useless feature of PHP.
		return collect($classLike->getTraitUses())
			->map(function (Node\Stmt\TraitUse $traitUseNode) {
				// Aliases keyed by trait class name
				$aliasNodes = collect($traitUseNode->adaptations)
					->whereInstanceOf(Alias::class)
					->groupBy(fn (Alias $alias) => (string) ($alias->trait ?? end($traitUseNode->traits)));

				return new TraitsUse(
					traits: collect($traitUseNode->traits)
						->map(function (Name $name) use ($aliasNodes) {
							$name = (string) $name;
							$aliasNodesForTrait = $aliasNodes[$name] ?? collect();

							$aliases = $aliasNodesForTrait
								->map(fn (Alias $alias) => [
									(string) $alias->method,
									((string) $alias->newName) ?: null,
									$alias->newModifier ?: null,
								])
								->values();

							return new TraitUse($name, $aliases);
						}),
					docComment: ((string) $traitUseNode->getDocComment()) ?: null,
				);
			});
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
			->map(fn (PropertyItem $property) => (string) $property->name);

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
