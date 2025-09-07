# Good PHP reflection

Reflection that accounts for features that are in static analysers, but aren't in the language yet.

### Why?

PHP is in a state where some very vital features only exist in userland (i.e. PHPStan),
like generics, tuple types, conditional types, type aliases and more. When you need 
reflection, you usually also need it to work with all of those userland features. Obviously,
PHP's built in reflection doesn't do that.

While custom reflection libraries do exist (like `roave/better-reflection`), none
of them are capable of parsing or understanding PHPDoc based types and features
from modern-day PHP. Furthermore, not all of them are performant enough to be used
in runtime (e.g. outside of tooling).

This library aims to be both runtime-ready (using some performant caching and lazy resolving)
and to cover the entire set of features of both built-in reflection and PHPStan features.

The perfect scenario here would be for PHPStan to simply extract it's own reflection
into a package, but this has already [been declined](https://github.com/phpstan/phpstan/discussions/4646) 
by the project author @ondrejmirtes, and understandably so. I've tried extracting it
myself by relocating parts of the PHPStan code, but it has proven to be complex,
unreliable and most importantly - slow to execute. 

So instead this projects attempts to fill the holes of the native reflection and modernize
the API in the process. Most of the API is defined with interfaces which you can extend
to implement things you need done.

### What does it do

Here are some of the features supported (or would be welcomed):

- [X] Reflection for classes, traits, interfaces and enums
- [x] Generics (reflect and substitute)
- [x] Tuple types
- [x] Anonymous classes
- [x] Blazing fast cache
- [ ] Support for `strict_types` configurations
- [ ] Conditional types
- [ ] Type aliases
- [ ] Extensions reflection (spl, zip, ds, dom etc)
- [ ] Template types inference for functions

### What not to expect

Unlikely to have support:
- "static" reflection (not executing PHP files that are being reflected)
- reflecting non-PHP or invalid PHP files

Unfortunately these creep into the territory of `roave/better-reflection`, which is just
way too slow for runtime use. 

### How reliable is this?

It's in alpha, so this is de-facto not stable. However, we do take measures to avoid
as much problems as possible. For example, the entire codebase uses the `max` level
of PHPStan with little to no ignored errors; it's covered with simple integration
and unit tests at 90% coverage.

That said, due to the dynamic nature of PHPDoc and complex type system it provides,
it's expected to encounter bugs and problems.

### How fast is this?

As much as possible of reflection information is cached to disk into `.php` files. When
you request a reflection of a type, it only does `require cache_file_for_something.php`,
wraps it in a reflection class and (optionally) substitutes template types. No
heavy parsing is done for previously reflected types.

Because of this, it's pretty fast (nanoseconds range) *after* initial caching. Both
full Native PHP reflection and Roave/BetterReflection are generally faster, but keep in 
mind this also has to parse AST and DocBlocks to extract generics and types. Still,
I believe it to be fast enough to actually be used in production if you enable the cache.

Here is a reference benchmark, performed on an M1 MacBook Pro with OpCache and JIT:

```
~/Projects/Personal/good-php/reflection> docker run -it --rm -v $"($env.PWD):/app" -v $"($env.PWD)/misc/opcache.ini:/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini" -w /app chialab/php-dev:8.2 composer benchmark 
> Composer\Config::disableProcessTimeout
> vendor/bin/phpbench run tests/Benchmark
PHPBench (1.4.0) running benchmarks... #standwithukraine
with configuration file: /app/phpbench.json
with PHP version 8.2.27, xdebug ✔, opcache ✔

\Tests\Benchmark\ThisReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.009ms (±4.68%) [2.149mb / 4.911mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.038ms (±4.53%) [3.487mb / 4.911mb]
    benchWarmWithFileCache # only name......I49 - Mo0.033ms (±2.12%) [2.229mb / 4.911mb]
    benchWarmWithFileCache # everything.....I49 - Mo0.068ms (±3.02%) [6.352mb / 6.385mb]
    benchCold # only name...................I199 - Mo2.519ms (±15.44%) [2.166mb / 4.911mb]
    benchCold # everything..................I199 - Mo2.701ms (±12.87%) [2.267mb / 4.911mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo72.206ms (±3.00%) [2.132mb / 4.911mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo77.608ms (±10.01%) [2.217mb / 4.911mb]

\Tests\Benchmark\NativeReflectionBench

    benchWarm # only name...................I49 - Mo0.001ms (±13.48%) [575.552kb / 4.910mb]
    benchWarm # everything..................I49 - Mo0.002ms (±9.02%) [575.616kb / 4.910mb]
    benchCold # only name...................I199 - Mo0.005ms (±39.39%) [576.376kb / 4.911mb]
    benchCold # everything..................I199 - Mo0.013ms (±32.23%) [576.376kb / 4.911mb]

\Tests\Benchmark\BetterReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.003ms (±4.81%) [3.096mb / 4.911mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.010ms (±3.97%) [3.137mb / 4.911mb]
    benchCold # only name...................I199 - Mo1.265ms (±17.02%) [3.116mb / 4.911mb]
    benchCold # everything..................I199 - Mo1.797ms (±13.45%) [3.103mb / 4.911mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo58.146ms (±9.89%) [3.095mb / 4.911mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo61.066ms (±3.91%) [3.074mb / 4.911mb]

Subjects: 9, Assertions: 0, Failures: 0, Errors: 0
~/Projects/Personal/good-php/reflection> docker run -it --rm -v $"($env.PWD)/benchmark/typhoon:/app" -v $"($env.PWD)/misc/opcache.ini:/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini" -v $"($env.PWD)/tests/Stubs:/app/tests/Stubs" -w /app chialab/php-dev:8.2 composer benchmark riant everything 
> Composer\Config::disableProcessTimeout
> vendor/bin/phpbench run src
PHPBench (1.4.0) running benchmarks... #standwithukraine
with configuration file: /app/phpbench.json
with PHP version 8.2.27, xdebug ✔, opcache ✔

\TyphoonReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.006ms (±10.36%) [1.975mb / 4.841mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.018ms (±3.40%) [2.042mb / 4.841mb]
    benchWarmWithFileCache # only name......I49 - Mo0.135ms (±2.14%) [2.031mb / 4.841mb]
    benchWarmWithFileCache # everything.....I49 - Mo0.148ms (±2.74%) [2.033mb / 4.841mb]
    benchCold # only name...................I199 - Mo6.987ms (±9.90%) [1.904mb / 4.840mb]
    benchCold # everything..................I199 - Mo7.288ms (±10.82%) [1.971mb / 4.840mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo96.134ms (±8.41%) [1.900mb / 4.841mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo96.932ms (±11.23%) [1.967mb / 4.841mb]

Subjects: 4, Assertions: 0, Failures: 0, Errors: 0
```

With cache, it's the slowest of them all. But the difference is in __nanoseconds__.

### How does it work

Unfortunately, it's not as simple as just using the native reflection and parsing some
PHPDocs on the side. Although PHP's Reflection is quite powerful, it doesn't provide all
the tools necessary to efficiently parse PHPDoc. Namely, there are a few limitations:
  - you can't access `use` statements (imports), needed to map "imported" classes in PHPDocs
  - you can't reliably access "immediate" (i.e. declared within that structure) interfaces, 
trait uses, constants, properties and methods - all needed for nested generic types
  - you can't access trait use docblocks, aliases or precedence - all needed for generics

Because of these limitations we have to rely on a mix of native reflection and AST parsing.
The general principle is this: collect as many bits of information from the native reflection
(because it's the fastest and most reliable) and some additional information from parsing
PHP files with `nikic/php-parser`, then combine them together, producing a "definition".

A "definition" is a term we use to denote a data-only class that holds reflection information
of a specific structure (i.e. it's generic type parameters, properties, methods etc), but
only of that specific structure (excluding inherited ones). This is done for a couple of reasons,
but the primary one is generics: this way it's easy to substitute them in the entire
inheritance tree by recursively mapping them to each supertype.

There are also definition *providers* which do exactly what they sound. You can chain as
many of them as you like and provide/override the reflection data with any source. By
default, it uses native reflection and `phpstan/phpdoc-parser` to gather all of the
information, but you can adapt any other reflection library as you wish.

Reflection, on the other hand, is the user-facing API. Instead of collecting the reflection data,
it simply "presents" the definition-provided one with a set of APIs:
  - meant for end-user
  - fully fledged API
  - untrivial to serialize/cache because of dependencies (such as Reflector)

Such approach allows to have a clear separation between cacheable data structures and
the reflection itself, which depends on the `Reflector` instance. 
