<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use ReflectionClass;
use ReflectionFunction;

/**
 * Provides some context for reflection from file AST nodes.
 */
class FileContextParser
{
	public function __construct(private readonly Parser $phpParser) {}

	public function parse(ReflectionClass|ReflectionFunction $reflection): ?FileContext
	{
		if (!$reflection->getFileName()) {
			return null;
		}

		$nodes = $this->phpParser->parse(
			file_get_contents($reflection->getFileName())
		);

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
