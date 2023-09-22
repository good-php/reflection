<?php

namespace GoodPhp\Reflection;

use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use GoodPhp\Reflection\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\Definition\Cache\StaticCacheDefinitionProvider;
use GoodPhp\Reflection\Definition\Fallback\FallbackDefinitionProvider;
use GoodPhp\Reflection\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\Definition\NativePHPDoc\Native\NativeTypeMapper;
use GoodPhp\Reflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\PhpDocTypeMapper;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\TypeAliasResolver;
use GoodPhp\Reflection\Reflector\Reflector;
use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;

class ReflectorBuilder
{
	private ?string $fileCachePath = null;

	private bool $memoryCache = false;

	public function withFileCache(string $path): self
	{
		$builder = clone $this;
		$builder->fileCachePath = $path;

		return $builder;
	}

	public function withMemoryCache(): self
	{
		$builder = clone $this;
		$builder->memoryCache = true;

		return $builder;
	}

	public function build(): Reflector
	{
		$typeAliasResolver = new TypeAliasResolver();
		$constExprParser = new ConstExprParser();
		$typeParser = new TypeParser($constExprParser);
		$phpDocParser = new PhpDocParser($typeParser, $constExprParser);
		$lexer = new Lexer();
		$phpDocStringParser = new PhpDocStringParser($lexer, $phpDocParser);

		$nativePhpDocDefinitionProvider = new NativePHPDocDefinitionProvider(
			$phpDocStringParser,
			new FileContextParser(
				new Parser\Php7(new Emulative([
					'usedAttributes' => ['comments', 'startLine'],
				]))
			),
			$typeAliasResolver,
			new NativeTypeMapper(),
			new PhpDocTypeMapper($typeAliasResolver),
		);

		if ($this->fileCachePath) {
			$nativePhpDocDefinitionProvider = new FileModificationCacheDefinitionProvider(
				$nativePhpDocDefinitionProvider,
				new VerifiedCache(
					new Psr16Cache(
						new PhpFilesAdapter(directory: $this->fileCachePath)
					)
				)
			);
		}

		$definitionProvider = new FallbackDefinitionProvider([
			new BuiltInSpecialsDefinitionProvider(),
			new BuiltInCoreDefinitionProvider(),
			$nativePhpDocDefinitionProvider,
		]);

		if ($this->memoryCache) {
			$definitionProvider = new StaticCacheDefinitionProvider(
				$definitionProvider,
				new Psr16Cache(new ArrayAdapter(storeSerialized: false))
			);
		}

		return new Reflector($definitionProvider);
	}
}
