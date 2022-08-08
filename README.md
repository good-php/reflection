# Better reflection

Reflection that accounts for features that are in static analysers, but aren't in the language yet.

### Features (implemented and todo):
- [X] Reflection for classes, traits, interfaces and enums
- [x] Generics (reflect and substitute)
- [x] Tuple types
- [x] Type system
- [X] Built-in types reflection
- [x] Anonymous classes
- [x] Blazing fast cache
- [ ] Support for `strict_types=false`
- [ ] Conditional types
- [ ] Type aliases
- [ ] Extensions reflection (spl, zip, ds, dom etc)
- [ ] Template types inference for functions

### What is _**not**_ planned to be supported:
- PHP dynamic properties/methods: `__get`, `__set`, `__call`, `__callStatic`, `__isset`, `__unset`, `@property`, `@method`, `@mixin`
- value types: `true`, `false` (both reported as `bool`),
  `null`, `123`, `1.0`, `'string'`, `'string'|'other'`, `Foo::SOME_CONSTANT`, `Foo::SOME_*`, 
  `SOME_CONSTANT`, `SOME_CONSTANT|OTHER_CONSTANT` (all reported as error types)
- special primitive types: `positive-int`, `int<0, 100>`, `int-mask<1, 2, 4>` (both reported as `int`), `non-empty-array` (reported as `array`),
  `callable-string`, `numeric-string`, `non-empty-string` (reported as `string`)
- array shapes (only those with explicit keys): `array{'foo': int, "bar": string}`

## Commands
Install dependencies:
`docker run -it --rm -v $PWD:/app -w /app composer install`

Run tests:
`docker run -it --rm -v $PWD:/app -w /app php:8.1-cli vendor/bin/pest`

Run php-cs-fixer on self:
`docker run -it --rm -v $PWD:/app -w /app composer cs-fix`
