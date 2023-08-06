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
in runtime (i.e. outside of tooling).

This library aims to be both runtime-ready (using some very very performant caching)
and to cover the entire set of features of both built-in reflection and PHPStan features.

The perfect scenario here would be for PHPStan to simply extract it's own reflection
into a package, but this has already [been declined](https://github.com/phpstan/phpstan/discussions/4646) 
by the project author @ondrejmirtes, and understandably so.

### What does it do

Here are some of the features supported:

- [X] Reflection for classes, traits, interfaces and enums
- [x] Generics (reflect and substitute)
- [x] Tuple types
- [x] Anonymous classes
- [x] Blazing fast cache
- [ ] Support for `strict_types=false`
- [ ] Conditional types
- [ ] Type aliases
- [ ] Extensions reflection (spl, zip, ds, dom etc)
- [ ] Template types inference for functions

### How reliable is this?

### How fast is this



### How does it work

It's based of native PHP reflection and `phpstan/phpdoc-parser`. It first gathers the most
possible reflection data from native reflection, then parses DocBlocks as needed and
passes that through PHPStan's own PHPDoc parser. That gets us all of the PHPStan
supported PHPDoc attributes, which we then do our best to actually match with native reflection.

After the reflection data is fully gathered, it's stored as a "definition" in a filesystem
PHP cache. When a reflection is requested, it does `require cache_file.php` which
simply pulls it the file from Opcache and constructs a definition instance. This
happens blazingly fast thanks to optimizations in `symfony/var-exporter`. If needed, 
these definitions could be stored in-memory for a near-instant reflection access.
