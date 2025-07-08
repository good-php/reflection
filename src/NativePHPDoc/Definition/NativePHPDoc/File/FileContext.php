<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File;

use ReflectionClass;

class FileContext
{
	/**
	 * @param array<string, FileClassLikeContext> $classLikes
	 * @param array<int, FileClassLikeContext>    $anonymousClassLikes
	 */
	public function __construct(
		public readonly array $classLikes,
		public readonly array $anonymousClassLikes,
	) {}

	/**
	 * @param ReflectionClass<covariant object> $reflection
	 */
	public function forClassLike(ReflectionClass $reflection): FileClassLikeContext
	{
		return !$reflection->isAnonymous() ?
			$this->classLikes[$reflection->getName()] :
			$this->anonymousClassLikes[$reflection->getStartLine()];
	}
}
