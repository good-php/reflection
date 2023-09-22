# Good PHP reflection

Reflection that accounts for features that are in static analysers, but aren't in the language yet.

### Why?

PHP is in a state where some very vital features only exist in userland (i.e. PHPStan),
like generics, tuple types, conditional types, type aliases and more. When you need 
reflection, you usually also need it work with all of those userland features. Obviously,
PHP's built in reflection doesn't do that.

And while custom reflection libraries do exist (like `roave/better-reflection`), none
of them actually try to parse, understand and help with PHPDoc based types and features
from modern-day PHP. Furthermore, not all of them are performant enough to be used
in runtime (e.g. outside of tooling).

This library aims to be both runtime-ready (using some very very performant caching)
and to cover the entire set of features of both built-in reflection and PHPStan features.

The perfect scenario here would be for PHPStan to simply extract it's own reflection
into a package, but this has already [been declined](https://github.com/phpstan/phpstan/discussions/4646) 
by the project author @ondrejmirtes, and understandably so.

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

### How fast is this

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

It's split into two parts: the "definitions" and reflection itself:

Definitions are the "heart" of reflection - they contain all the information about PHP
structures, but do not have any user-facing APIs:
  - meant for internal use by reflection
  - simple "data" classes without any state, methods or dependencies
  - efficient, fast
  - trivial to serialize and/or cache to disk

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
