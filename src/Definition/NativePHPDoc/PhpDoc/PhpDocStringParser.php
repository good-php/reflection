<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

class PhpDocStringParser
{
	public function __construct(
		private readonly Lexer $lexer,
		private readonly PhpDocParser $phpDocParser
	) {}

	/**
	 * @param string|ReflectionClass<object>|ReflectionProperty|ReflectionMethod|null $input
	 */
	public function parse(string|ReflectionClass|ReflectionProperty|ReflectionMethod|null $input): PhpDocNode
	{
		if ($input instanceof Reflector) {
			$input = $input->getDocComment() ?: null;
		}

		if (!$input) {
			return new PhpDocNode([]);
		}

		$tokens = new TokenIterator($this->lexer->tokenize($input));

		return $this->phpDocParser->parse($tokens);
	}
}
