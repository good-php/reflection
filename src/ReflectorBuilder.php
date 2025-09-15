<?php

namespace GoodPhp\Reflection;

use Composer\InstalledVersions;
use DateInterval;
use GoodPhp\Reflection\Cache\StaticCacheReflector;
use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\StaticCacheDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\FallbackDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\LazyDefinitionProvider;
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
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

class ReflectorBuilder
{
	/** @var list<DefinitionProvider> */
	private array $addedFallbackDefinitionProviders = [];

	private ?DefinitionProvider $innerDefinitionProvider = null;

	/** @var callable(DefinitionProvider): DefinitionProvider|null */
	private mixed $wrapInnerDefinitionProvider = null;

	private ?DefinitionProvider $definitionProvider = null;

	/** @var callable(DefinitionProvider): DefinitionProvider|null */
	private mixed $wrapDefinitionProvider = null;

	private (CacheItemPoolInterface&NamespacedPoolInterface)|null $inMemoryCache = null;

	/**
	 * Use file cache to cache definitions of the inner provider. Can (and should) be used together with {@see withMemoryCache()}
	 */
	public function withFileCache(?string $path = null, ?DateInterval $ttl = null): self
	{
		$path ??= sys_get_temp_dir() . '/good-php-reflection';

		return $this->wrapInnerDefinitionProvider(
			fn (DefinitionProvider $provider) => new FileModificationCacheDefinitionProvider(
				$provider,
				new VerifiedCache(
					new Psr16Cache(
						new PhpFilesAdapter(
							namespace: (string) InstalledVersions::getReference('good-php/reflection'),
							defaultLifetime: (int) $ttl?->format('s'),
							directory: $path,
						)
					)
				)
			)
		);
	}

	/**
	 * Use memory cache to cache all definitions. Can (and should) be used together with {@see withFileCache()}
	 */
	public function withMemoryCache(int $maxItems = 100, ?DateInterval $ttl = null): self
	{
		$this->inMemoryCache = new ArrayAdapter(
			defaultLifetime: (int) $ttl?->format('s'),
			storeSerialized: false,
			maxItems: $maxItems,
		);

		return $this->wrapDefinitionProvider(
			fn (DefinitionProvider $provider) => new StaticCacheDefinitionProvider(
				$provider,
				new Psr16Cache($this->inMemoryCache->withSubNamespace('definitions'))
			)
		);
	}

	public function withAddedFallbackDefinitionProvider(DefinitionProvider $provider): self
	{
		$builder = clone $this;
		$builder->addedFallbackDefinitionProviders[] = $provider;

		return $builder;
	}

	public function withInnerDefinitionProvider(DefinitionProvider $provider): self
	{
		$builder = clone $this;
		$builder->innerDefinitionProvider = $provider;

		return $builder;
	}

	/**
	 * @param callable(DefinitionProvider): DefinitionProvider $wrap
	 */
	public function wrapInnerDefinitionProvider(callable $wrap): self
	{
		$builder = clone $this;
		$builder->wrapInnerDefinitionProvider = $wrap;

		return $builder;
	}

	public function withDefinitionProvider(DefinitionProvider $provider): self
	{
		$builder = clone $this;
		$builder->definitionProvider = $provider;

		return $builder;
	}

	/**
	 * @param callable(DefinitionProvider): DefinitionProvider $wrap
	 */
	public function wrapDefinitionProvider(callable $wrap): self
	{
		$builder = clone $this;
		$builder->wrapDefinitionProvider = $wrap;

		return $builder;
	}

	public function build(): Reflector
	{
		$reflector = new DefinitionProviderReflector($this->definitionProvider());

		if ($this->inMemoryCache) {
			$reflector = new StaticCacheReflector(
				$reflector,
				new Psr16Cache($this->inMemoryCache->withSubNamespace('reflections'))
			);
		}

		return $reflector;
	}

	private function innerDefinitionProvider(): DefinitionProvider
	{
		// Resolving some of these dependencies is quite expensive. So if we can
		// avoid doing so with fully cached definitions, then we should do so :)
		$provider = $this->innerDefinitionProvider ?? new LazyDefinitionProvider(static function () {
			$config = new ParserConfig(usedAttributes: []);
			$typeAliasResolver = new TypeAliasResolver();
			$constExprParser = new ConstExprParser($config);
			$typeParser = new TypeParser($config, $constExprParser);
			$phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
			$lexer = new Lexer($config);
			$phpDocStringParser = new PhpDocStringParser($lexer, $phpDocParser);

			return new NativePHPDocDefinitionProvider(
				$phpDocStringParser,
				new FileContextParser(
					(new ParserFactory())->createForNewestSupportedVersion(),
				),
				$typeAliasResolver,
				new NativeTypeMapper(),
				new PhpDocTypeMapper($typeAliasResolver),
			);
		});

		return $this->wrapInnerDefinitionProvider ? ($this->wrapInnerDefinitionProvider)($provider) : $provider;
	}

	private function definitionProvider(): DefinitionProvider
	{
		$provider = $this->definitionProvider ?? new FallbackDefinitionProvider($this->fallbackDefinitionProviders());

		return $this->wrapDefinitionProvider ? ($this->wrapDefinitionProvider)($provider) : $provider;
	}

	/**
	 * @return list<DefinitionProvider>
	 */
	private function fallbackDefinitionProviders(): array
	{
		return [
			...$this->addedFallbackDefinitionProviders,
			new BuiltInSpecialsDefinitionProvider(),
			new BuiltInCoreDefinitionProvider(),
			$this->innerDefinitionProvider(),
		];
	}
}
