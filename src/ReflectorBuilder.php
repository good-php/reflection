<?php

namespace GoodPhp\Reflection;

use Composer\InstalledVersions;
use DateInterval;
use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\StaticCacheDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\Fallback\FallbackDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\Native\NativeTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\TypeAliasResolver;
use GoodPhp\Reflection\NativePHPDoc\DefinitionProviderReflector;
use PhpParser\ParserFactory;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;

class ReflectorBuilder
{
	private ?DefinitionProvider $innerDefinitionProvider = null;

	private ?DefinitionProvider $definitionProvider = null;

	public function withFileCache(?string $path = null, ?DateInterval $ttl = null): self
	{
		$path ??= sys_get_temp_dir() . '/good-php-reflection';

		return $this->withInnerDefinitionProvider(new FileModificationCacheDefinitionProvider(
			$this->innerDefinitionProvider(),
			new VerifiedCache(
				new Psr16Cache(
					new PhpFilesAdapter(
						namespace: (string) InstalledVersions::getReference('good-php/reflection'),
						defaultLifetime: (int) $ttl?->format('s'),
						directory: $path,
					)
				)
			)
		));
	}

	public function withMemoryCache(int $maxItems = 100, ?DateInterval $ttl = null): self
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
		return new DefinitionProviderReflector($this->definitionProvider());
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

		$config = new ParserConfig(usedAttributes: []);
		$typeAliasResolver = new TypeAliasResolver();
		$constExprParser = new ConstExprParser($config);
		$typeParser = new TypeParser($config, $constExprParser);
		$phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
		$lexer = new Lexer($config);
		$phpDocStringParser = new PhpDocStringParser($lexer, $phpDocParser);

		return $this->innerDefinitionProvider = new NativePHPDocDefinitionProvider(
			$phpDocStringParser,
			new FileContextParser(
				(new ParserFactory())->createForNewestSupportedVersion(),
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
