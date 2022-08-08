<?php

namespace GoodPhp\Reflection;

use GoodPhp\Reflection\Cache\Verified\Storage\CacheStorage;
use GoodPhp\Reflection\Cache\Verified\Storage\SymfonyVarExportCacheStorage;
use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use GoodPhp\Reflection\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use GoodPhp\Reflection\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\Definition\DefinitionProvider;
use GoodPhp\Reflection\Definition\Fallback\FallbackDefinitionProvider;
use GoodPhp\Reflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\PhpDocTypeMapper;
use GoodPhp\Reflection\Definition\NativePHPDoc\PhpDoc\TypeAliasResolver;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\TypeComparator;
use Illuminate\Container\Container;
use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Psr\Container\ContainerInterface;

class ReflectionBuilder
{
	private Container $container;

	public function __construct()
	{
		$this->container = new Container();

		$this->container->singleton(Parser::class, fn () => new Parser\Php7(new Emulative()));
		$this->container->singleton(TypeAliasResolver::class);
		$this->container->singleton(PhpDocTypeMapper::class);
		$this->container->singleton(ConstExprParser::class);
		$this->container->singleton(TypeParser::class);
		$this->container->singleton(PhpDocParser::class);
		$this->container->singleton(Lexer::class);
		$this->container->singleton(PhpDocStringParser::class);
		$this->container->singleton(NativePHPDocDefinitionProvider::class);
		$this->container->singleton(TypeComparator::class);

		$this->container->singleton(
			DefinitionProvider::class,
			fn (Container $container) => new FallbackDefinitionProvider([
				$container->make(BuiltInSpecialsDefinitionProvider::class),
				$container->make(BuiltInCoreDefinitionProvider::class),
				$container->make(NativePHPDocDefinitionProvider::class),
			])
		);
		$this->container->singleton(Reflector::class);
	}

	public function __clone(): void
	{
		$this->container = clone $this->container;
	}

	public function withCache(string $path): self
	{
		$builder = clone $this;

		$builder->container->singleton(CacheStorage::class, fn () => new SymfonyVarExportCacheStorage($path));
		$builder->container->singleton(VerifiedCache::class);
		$builder->container->singleton(
			DefinitionProvider::class,
			fn (Container $container) => new FallbackDefinitionProvider([
				$container->make(BuiltInSpecialsDefinitionProvider::class),
				$container->make(BuiltInCoreDefinitionProvider::class),
				new FileModificationCacheDefinitionProvider(
					$container->make(NativePHPDocDefinitionProvider::class),
					$container->make(VerifiedCache::class)
				),
			])
		);

		return $builder;
	}

	public function build(): ContainerInterface
	{
		return $this->container;
	}
}
