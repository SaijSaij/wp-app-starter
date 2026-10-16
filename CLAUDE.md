# CLAUDE.md – wp-app-starter

Composer project `saij/wp-app-starter`: `composer create-project saij/wp-app-starter <dir>` creates a theme, child theme or plugin built with [wp-app-framework](https://github.com/saijsaij/wp-app-framework). The installer (`installer/`) asks the questions in `post-create-project-cmd`, renders the parts of `skeleton/` into the project, removes the starter's own files and runs `composer update` and, with Node 24, `npm install` and `npm run build`. Framework principles and rules: the framework's CLAUDE.md and its [documentation](https://saijsaij.github.io/wp-app-framework/); this file covers the starter.

## Rules

- A created project must pass everything its own development asks for: `composer lint`, `npm run lint`, `composer build-manifest`, the browser tests of the checkout and of the release ZIP. `tests/projects` checks exactly that for a theme, a child theme and a plugin with every option; `GeneratorTest` renders every kind with every combination of options.
- The installer runs in the PHP of the user's Composer, not in the projects' PHP 8.5: PHP 8.1 syntax and functions only, no dependencies besides Composer's own classes (PHPStan checks with `phpVersion: 80100`).
- Explicit over implicit, as in the framework: no default for the namespace of the framework (content refers to it), invalid answers are asked again or, without interaction, an error naming the variable `WP_APP_STARTER_<KEY>`.
- The generated projects make no assumptions about a workspace, wp-dev-env or a framework checkout: pools without package links, the parent theme of a child next to it or as ZIP in `tests/parent/`, plugins on the default theme of WordPress.
- Templates in `skeleton/` must not depend on the length of answers where a formatter would wrap (Prettier, PHP-CS-Fixer): put long or variable values into constants or separate lines.
- Class properties are never `public`; everything in this repository is English; other repositories are linked by GitHub URL.

## Commands

Run from this directory, with [wp-test-env](https://github.com/saijsaij/wp-test-env) and wp-app-framework checked out next to it.

```sh
../wp-test-env/bin/tools -w wp-app-starter/tests composer install
../wp-test-env/bin/tools -w wp-app-starter/tests composer test   # PHPUnit
../wp-test-env/bin/tools -w wp-app-starter/tests composer lint   # PHPStan + PHP-CS-Fixer; fix: composer fix
tests/create <slug> <theme|child-theme|plugin> [NAME=value]...    # one project in the workspace, without interaction
tests/projects                                                    # theme, child theme and plugin with every option, checked in wp-test-env
```

`tests/create` uses a Composer home of its own (`.starter-composer/` in the workspace) with path repositories for the starter checkout and the framework checkout (as the version the starter requires); its repositories also apply to the `composer update` of the installer. The created projects land in the workspace; remove them afterwards. To try the questions, run `composer create-project` through `../wp-test-env/bin/tools` in a terminal with `COMPOSER_HOME=/workspace/.starter-composer` after a `tests/create`.

## Pitfalls

- Composer does not read `composer.json` again after `post-root-package-install` (Composer 2.10, `CreateProjectCommand`), so the installer works in `post-create-project-cmd` and runs `composer update` itself (`COMPOSER_BINARY`).
- The class name of the Composer autoloader (`ComposerAutoloaderInit<suffix>`) is kept from an existing `vendor/autoload.php`; after create-project it is the starter's, the same for every project, and two projects in one WordPress collide in development mode. The installer deletes `vendor/autoload.php` before its `composer update` (`AutoloadGenerator::dump()`).
- `__NAMESPACE__`, `__DIR__`, `__FILE__` are PHP constants, not placeholders; the namespace of the framework is `__WP_NAMESPACE__`. An unknown `__PLACEHOLDER__` fails the rendering.
- Files of a part whose name starts with `_` are renamed to `.` (`_gitignore`); placeholders in names are replaced first (`__SLUG__.php`).
- Everything is rendered in memory before the starter's files are removed, so an error leaves the directory as it was.
- `.gitattributes` keeps `tests/`, `.github/` and this file out of the archive that create-project downloads.

## Keep in sync

- `require` of `saij/wp-app-framework` in `composer.json` follows the framework's minor versions: after a framework release, raise it (e.g. `^0.17`) and tag the starter. The generated release workflow uses the tag of the installed framework.
- Versions in the generated `composer.json` and `package.json` (`installer/Generator.php`: PHPStan, PHP-CS-Fixer, `@wordpress/scripts`, Tailwind, Playwright) follow the framework's test projects (theme-dev-test); `@playwright/test` is exact, because wp-test-env runs the image of that version.
- `skeleton/e2e/tests/e2e/` follows the browser test setup of theme-dev-test (`playwright.config.js`, `fixtures.js`).
- Tags `v<semver>`; every GitHub release gets short release notes (what changed for created projects, the framework version it requires) instead of the generated compare link alone.
