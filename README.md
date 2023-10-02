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
\Tests\Benchmark\ThisReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.011ms (±15.06%) [3.856mb / 4.779mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.137ms (±5.25%) [9.970mb / 9.988mb]
    benchWarmWithFileCache # only name......I49 - Mo0.047ms (±11.98%) [6.917mb / 6.958mb]
    benchWarmWithFileCache # everything.....I49 - Mo0.172ms (±4.64%) [13.097mb / 13.114mb]
    benchCold # only name...................I199 - Mo2.384ms (±12.80%) [2.143mb / 4.779mb]
    benchCold # everything..................I199 - Mo2.506ms (±18.67%) [2.276mb / 4.779mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo74.279ms (±18.78%) [2.092mb / 4.779mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo72.901ms (±4.37%) [2.188mb / 4.779mb]

\Tests\Benchmark\BetterReflectionBench

    benchWarmWithMemoryCache # only name....I49 - Mo0.005ms (±8.26%) [3.085mb / 4.779mb]
    benchWarmWithMemoryCache # everything...I49 - Mo0.016ms (±5.70%) [3.093mb / 4.779mb]
    benchCold # only name...................I199 - Mo1.693ms (±6.13%) [3.104mb / 4.779mb]
    benchCold # everything..................I199 - Mo2.299ms (±14.67%) [3.116mb / 4.779mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo59.184ms (±5.79%) [3.084mb / 4.779mb]
    benchColdIncludingInitializationAndAuto.I199 - Mo63.590ms (±18.52%) [3.092mb / 4.779mb]

\Tests\Benchmark\NativeReflectionBench

    benchWarm # only name...................I49 - Mo0.001ms (±9.11%) [517.504kb / 4.778mb]
    benchWarm # everything..................I49 - Mo0.004ms (±6.08%) [517.568kb / 4.778mb]
    benchCold # only name...................I199 - Mo0.009ms (±55.16%) [518.488kb / 4.779mb]
    benchCold # everything..................I199 - Mo0.022ms (±23.29%) [518.488kb / 4.779mb]
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
