# __NAME__

@@if description
__DESCRIPTION__

@@endif
@@if theme
A block theme
@@endif
@@if child-theme
A child theme of `__PARENT__`
@@endif
@@if plugin
A WordPress plugin
@@endif
built with [wp-app-framework](https://webcodingcat.github.io/wp-app-framework/): blocks, hooks and settings are PHP classes with attributes in `src/`, their markup is Twig, their styles Tailwind CSS.

- Slug (directory name): `__SLUG__`
- Namespace: `__WP_NAMESPACE__`, the name in front of blocks (`__WP_NAMESPACE__/…`), REST routes, settings options, post types and tables; content refers to it, so keep it.
- PHP namespace: `__PHP_NAMESPACE__\` in `src/`

## Requirements

WordPress 7.1 with PHP 8.5, Composer and Node 24 for the build.
@@if child-theme
The parent theme `__PARENT__` must be installed.
@@endif

## Development

```sh
composer install
npm install
npm run build             # editor runtime, scripts and styles into build/
npm run start             # the same, again on every change
composer build-manifest   # checks attributes and services; writes nothing
```

Put the directory into `wp-content/__KIND_DIRECTORY__/` of a WordPress installation (or link it there) and activate it. The framework runs in development mode and builds its manifest itself; you only rebuild the scripts and styles.

@@if example
`src/Block/Teaser/` holds an example block, `__WP_NAMESPACE__/teaser`. Delete the directory when you no longer need it.

@@endif
The [documentation](https://webcodingcat.github.io/wp-app-framework/) explains blocks, the editor, settings, post types and everything else.

@@if quality
## Checks

```sh
composer lint    # PHPStan (level max) and PHP-CS-Fixer (PER-CS); fix: composer fix
npm run lint     # ESLint; fix: npm run format
```

@@endif
## Release

```sh
composer release   # builds dist/__SLUG__-<version>.zip
```

The release build prefixes the framework and all dependencies, so the ZIP runs in any WordPress next to other themes and plugins built with the framework. It takes the files Git knows, so commit first; files marked `export-ignore` in `.gitattributes` stay out of the ZIP. The version is `Version:` in `__VERSION_FILE__`.

@@if ci
A tag `v<version>` (equal to that version) runs `.github/workflows/release.yml`: the release workflow of the framework builds the ZIP, tests it in a fresh WordPress and attaches it to the GitHub release of the tag.
@@if child-theme
It installs the child theme on top of the parent theme release `__WITH_THEME__`.
@@endif
@@if plugin
@@if with-theme
It installs the plugin next to the theme release `__WITH_THEME__`.
@@else
It runs the plugin on the default theme of WordPress.
@@endif
@@endif
Keep the framework version of the workflow (`consumer-release.yml@__FRAMEWORK_REF__`) at the one your lock file installs.

@@endif
@@if e2e
## Browser tests

The browser tests in `tests/e2e/` run with [wp-test-env](https://github.com/webcodingcat/wp-test-env), which starts fresh WordPress instances in Docker. Check it out next to this project; the directory that contains both is its workspace:

```sh
git clone https://github.com/webcodingcat/wp-test-env.git ../wp-test-env
cd ../wp-test-env
bin/run ../__SLUG__/tests/dev.pools       # the checkout, in development mode
bin/run ../__SLUG__/tests/release.pools   # the release ZIP
```

@@if child-theme
The development pool expects the parent theme `__PARENT__` next to this project, ready to run. The release pool takes the parent theme from the newest ZIP in `tests/parent/`, which Git ignores.

@@endif
@@if plugin
The plugin runs on the default theme of WordPress in both pools.

@@endif
Every test fails on console warnings and errors (`tests/e2e/fixtures.js`).

@@endif
## License

GPL-2.0-or-later
