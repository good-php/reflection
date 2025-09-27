<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ReflectionClass;
use ReflectionClassConstant;
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
	 * @param string|ReflectionClass<object>|ReflectionClassConstant|ReflectionProperty|ReflectionMethod|null $input
	 */
	public function parse(string|ReflectionClass|ReflectionClassConstant|ReflectionProperty|ReflectionMethod|null $input): ParsedPhpDoc
	{
		if ($input instanceof Reflector) {
			$input = $input->getDocComment() ?: null;
		}

		if (!$input) {
			return ParsedPhpDoc::empty();
		}

		$tokens = new TokenIterator($this->lexer->tokenize($input));

		return new ParsedPhpDoc($this->phpDocParser->parse($tokens));
	}
}
