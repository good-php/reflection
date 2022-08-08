<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\File;

use Illuminate\Support\Collection;
use ReflectionClass;

class FileContext
{
	/**
	 * @param Collection<string, FileClassLikeContext> $classLikes
	 * @param Collection<string, FileClassLikeContext> $anonymousClassLikes
	 */
	public function __construct(
		public readonly Collection $classLikes,
		public readonly Collection $anonymousClassLikes,
	) {
	}

	public function forClassLike(ReflectionClass $reflection): FileClassLikeContext
	{
		return !$reflection->isAnonymous() ?
			$this->classLikes[$reflection->getName()] :
			$this->anonymousClassLikes[$reflection->getStartLine()];
	}
}
