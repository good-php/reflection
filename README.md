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

Here is a reference benchmark, performed on an M1 MacBook Pro with OpCache:

```
PHPBench (1.4.0) running benchmarks... #standwithukraine
with configuration file: /app/phpbench.json
with PHP version 8.2.27, xdebug ✔, opcache ✔

\Tests\Benchmark\ThisReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.014ms (±3.52%) [2.655mb / 5.119mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.101ms (±2.74%) [4.040mb / 5.119mb]
    benchWarmWithFileCache # only name......I49 - Mo0.046ms (±13.64%) [2.824mb / 5.119mb]
    benchWarmWithFileCache # everything.....I49 - Mo0.138ms (±4.13%) [6.883mb / 6.917mb]
    benchCold # only name...................I199 - Mo3.049ms (±6.14%) [2.848mb / 5.119mb]
    benchCold # everything..................I199 - Mo3.231ms (±8.59%) [2.892mb / 5.119mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo70.248ms (±13.82%) [2.629mb / 5.119mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo74.509ms (±6.07%) [2.648mb / 5.119mb]

\TyphoonReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.007ms (±3.40%) [2.743mb / 5.007mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.035ms (±4.54%) [2.855mb / 5.007mb]
    benchWarmWithFileCache # only name......I49 - Mo0.565ms (±16.26%) [2.768mb / 5.007mb]
    benchWarmWithFileCache # everything.....I49 - Mo0.610ms (±4.60%) [2.880mb / 5.007mb]
    benchCold # only name...................I199 - Mo8.304ms (±10.59%) [2.795mb / 5.007mb]
    benchCold # everything..................I199 - Mo9.099ms (±32.67%) [2.781mb / 5.007mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo93.155ms (±9.28%) [2.797mb / 5.007mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo94.742ms (±9.53%) [2.798mb / 5.007mb]

\Tests\Benchmark\NativeReflectionBench

    benchWarm # only name...................I49 - Mo0.001ms (±4.52%) [718.136kb / 5.118mb]
    benchWarm # everything..................I49 - Mo0.004ms (±6.02%) [718.200kb / 5.118mb]
    benchCold # only name...................I199 - Mo0.006ms (±46.22%) [718.960kb / 5.119mb]
    benchCold # everything..................I199 - Mo0.018ms (±28.15%) [718.960kb / 5.119mb]

\Tests\Benchmark\BetterReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.006ms (±5.14%) [3.321mb / 5.119mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.024ms (±2.55%) [3.296mb / 5.119mb]
    benchCold # only name...................I199 - Mo1.551ms (±7.82%) [3.341mb / 5.119mb]
    benchCold # everything..................I199 - Mo2.330ms (±5.83%) [3.328mb / 5.119mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo54.618ms (±11.56%) [3.320mb / 5.119mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo58.794ms (±15.56%) [3.299mb / 5.119mb]

```

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
