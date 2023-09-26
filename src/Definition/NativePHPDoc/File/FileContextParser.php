<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use ReflectionClass;
use ReflectionFunction;
use Webmozart\Assert\Assert;

/**
 * Provides some context for reflection from file AST nodes.
 */
class FileContextParser
{
	public function __construct(private readonly Parser $phpParser) {}

	/**
	 * @param ReflectionClass<object>|ReflectionFunction $reflection
	 */
	public function parse(ReflectionClass|ReflectionFunction $reflection): ?FileContext
	{
		$fileName = $reflection->getFileName();

		if (!$fileName) {
			return null;
		}

		$nodes = $this->phpParser->parse(file_get_contents($fileName));

		Assert::notNull($nodes, "Failed to parse [{$fileName}] for Reflection; it probably contains syntax errors.");

		$traverser = new NodeTraverser();
		$traverser->addVisitor($nameResolverVisitior = new NameResolver());
		$traverser->addVisitor($classLikesVisitor = new ClassLikeContextParsingVisitor($nameResolverVisitior));
		$traverser->traverse($nodes);

		return new FileContext(
			classLikes: $classLikesVisitor->classLikes,
			anonymousClassLikes: $classLikesVisitor->anonymousClassLikes
		);
	}
}
