<?php

namespace GoodPhp\Reflection;

use DateInterval;
use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use GoodPhp\Reflection\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\Definition\Cache\StaticCacheDefinitionProvider;
use GoodPhp\Reflection\Definition\DefinitionProvider;
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
	private ?DefinitionProvider $innerDefinitionProvider = null;

	private ?DefinitionProvider $definitionProvider = null;

	public function withFileCache(string $path = null, DateInterval $ttl = null): self
	{
		$path ??= $path ?? sys_get_temp_dir() . '/good-php-reflection';

		return $this->withInnerDefinitionProvider(new FileModificationCacheDefinitionProvider(
			$this->innerDefinitionProvider(),
			new VerifiedCache(
				new Psr16Cache(
					new PhpFilesAdapter(
						defaultLifetime: (int) $ttl?->format('s'),
						directory: $path,
					)
				)
			)
		));
	}

	public function withMemoryCache(int $maxItems = 100, DateInterval $ttl = null): self
	{
		return $this->withDefinitionProvider(new StaticCacheDefinitionProvider(
			$this->definitionProvider(),
			new Psr16Cache(new ArrayAdapter(
				defaultLifetime: (int) $ttl?->format('s'),
				storeSerialized: false,
				maxItems: $maxItems,
			))
		));
	}

	public function build(): Reflector
	{
		return new Reflector($this->definitionProvider());
	}

	public function withInnerDefinitionProvider(DefinitionProvider $provider): self
	{
		$builder = clone $this;
		$builder->innerDefinitionProvider = $provider;

		return $builder;
	}

	public function withDefinitionProvider(DefinitionProvider $provider): self
	{
		$builder = clone $this;
		$builder->definitionProvider = $provider;

		return $builder;
	}

	public function innerDefinitionProvider(): DefinitionProvider
	{
		if ($this->innerDefinitionProvider) {
			return $this->innerDefinitionProvider;
		}

		$typeAliasResolver = new TypeAliasResolver();
		$constExprParser = new ConstExprParser();
		$typeParser = new TypeParser($constExprParser);
		$phpDocParser = new PhpDocParser($typeParser, $constExprParser);
		$lexer = new Lexer();
		$phpDocStringParser = new PhpDocStringParser($lexer, $phpDocParser);

		return $this->innerDefinitionProvider = new NativePHPDocDefinitionProvider(
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
	}

	public function definitionProvider(): DefinitionProvider
	{
		return $this->definitionProvider ??= new FallbackDefinitionProvider([
			new BuiltInSpecialsDefinitionProvider(),
			new BuiltInCoreDefinitionProvider(),
			$this->innerDefinitionProvider(),
		]);
	}
}
