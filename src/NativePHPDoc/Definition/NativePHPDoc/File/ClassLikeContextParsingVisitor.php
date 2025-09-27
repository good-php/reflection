<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitsUse;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext\TraitUse;
use Illuminate\Support\Collection;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
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
	/** @var array<string, FileClassLikeContext> */
	public array $classLikes = [];

	/** @var array<int, FileClassLikeContext> */
	public array $anonymousClassLikes = [];

	public function __construct(
		private readonly NameResolver $nameResolverVisitor,
	) {}

	public function enterNode(Node $node)
	{
		if (
			!$node instanceof Class_ &&
			!$node instanceof Interface_ &&
			!$node instanceof Trait_ &&
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
			declaredConstants: $this->constants($node),
			declaredProperties: $this->properties($node),
			declaredMethods: $this->methods($node),
		);

		if ($node->name) {
			$this->classLikes[(string) $node->namespacedName] = $context;
		} else {
			Assert::keyNotExists(
				$this->anonymousClassLikes,
				$node->getStartLine(),
				'Only one anonymous class-like can be defined within a single line due to PHPs limitations.'
			);

			$this->anonymousClassLikes[$node->getStartLine()] = $context;
		}

		return null;
	}

	/**
	 * @return array<class-string, list<string>>
	 */
	public function excludedTraitMethods(ClassLike $classLike): array
	{
		/** @var array<class-string, list<string>> */
		return collect($classLike->getTraitUses())
			->flatMap(
				fn (Node\Stmt\TraitUse $traitUseNode) => collect($traitUseNode->adaptations)->whereInstanceOf(Precedence::class)
			)
			->reduce(function (array $carry, Precedence $precedence) {
				foreach ($precedence->insteadof as $insteadof) {
					/** @var array<string, list<string>> $carry */
					$carry[(string) $insteadof] ??= [];
					$carry[(string) $insteadof][] = (string) $precedence->method;
				}

				return $carry;
			}, []);
	}

	/**
	 * @return list<string>
	 */
	private function implementsInterfaces(ClassLike $classLike): array
	{
		/** @var list<Name> $nameNodes */
		$nameNodes = match (true) {
			$classLike instanceof Class_     => $classLike->implements,
			$classLike instanceof Interface_ => $classLike->extends,
			$classLike instanceof Enum_      => $classLike->implements,
			default                          => [],
		};

		return array_map(fn (Name $name) => (string) $name, $nameNodes);
	}

	/**
	 * @return array<string, string>
	 */
	private function uses(NameContext $nameContext): array
	{
		static $aliasesProperty;

		if (!$aliasesProperty) {
			$aliasesProperty = new ReflectionProperty(NameContext::class, 'aliases');
		}

		/** @var array<string, Name> $uses */
		/** @phpstan-ignore-next-line */
		$uses = $aliasesProperty->getValue($nameContext)[Node\Stmt\Use_::TYPE_NORMAL] ?? [];

		return array_map(fn (Name $name) => (string) $name, $uses);
	}

	/**
	 * @return list<TraitsUse>
	 */
	private function traitsUses(ClassLike $classLike): array
	{
		// You don't wanna know how much time I spent researching how these stupid traits work in PHP.
		// What's crazy here is that even Roave/BetterReflection currently gets it wrong and doesn't
		// work the same way traits actually work in PHP. It's so unnecessarily overcomplicated.
		//
		// Not only can you use the same trait multiple times, you can also create infinite copies of a method
		// by aliases (which doesn't actually remove the "original" method, just adds a new one on top)
		// and even remove methods from used traits with "precedence".
		// After __halt_compiler, this is definitely the most useless feature of PHP.
		return array_map(function (Node\Stmt\TraitUse $traitUseNode) {
			// Aliases keyed by trait class name
			$aliasNodes = collect($traitUseNode->adaptations)
				->whereInstanceOf(Alias::class)
				->groupBy(fn (Alias $alias) => (string) ($alias->trait ?? end($traitUseNode->traits)))
				->map(
					fn (Collection $attributes) => $attributes->all()
				)
				->all();

			return new TraitsUse(
				traits: array_map(function (Name $name) use ($aliasNodes) {
					$name = (string) $name;
					$aliasNodesForTrait = $aliasNodes[$name] ?? [];

					$aliases = array_map(fn (Alias $alias) => [
						(string) $alias->method,
						((string) $alias->newName) ?: null,
						$alias->newModifier ?: null,
					], $aliasNodesForTrait);

					return new TraitUse($name, array_values($aliases));
				}, $traitUseNode->traits),
				docComment: ((string) $traitUseNode->getDocComment()) ?: null,
			);
		}, $classLike->getTraitUses());
	}

	/**
	 * @return list<string>
	 */
	private function constants(ClassLike $classLike): array
	{
		return collect($classLike->stmts)
			->whereInstanceOf(ClassConst::class)
			->flatMap(fn (ClassConst $constant) => $constant->consts)
			->map(fn (Const_ $constant) => (string) $constant->name)
			->all();
	}

	/**
	 * @return list<string>
	 */
	private function properties(ClassLike $classLike): array
	{
		if (!$classLike instanceof Class_ && !$classLike instanceof Trait_) {
			return [];
		}

		$properties = collect($classLike->stmts)
			->whereInstanceOf(Property::class)
			->flatMap(fn (Property $node) => $node->props)
			->map(fn (PropertyItem $property) => (string) $property->name)
			->all();

		/** @var ClassMethod|null $constructor */
		$constructor = collect($classLike->stmts)
			->first(fn (Node $node) => $node instanceof ClassMethod && (string) $node->name === '__construct');

		if (!$constructor) {
			return array_values($properties);
		}

		$promoted = collect($constructor->params)
			->filter(fn (Node\Param $node) => (bool) ($node->flags & Class_::VISIBILITY_MODIFIER_MASK))
			->map(function (Node\Param $node): string {
				Assert::isInstanceOf($node->var, Node\Expr\Variable::class);
				Assert::string($node->var->name);

				return $node->var->name;
			});

		return [...$properties, ...$promoted];
	}

	/**
	 * @return list<string>
	 */
	private function methods(ClassLike $classLike): array
	{
		return collect($classLike->stmts)
			->whereInstanceOf(ClassMethod::class)
			->map(fn (ClassMethod $method) => (string) $method->name)
			->all();
	}
}
