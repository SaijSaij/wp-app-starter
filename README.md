# wp-app-starter

Creates a theme, a child theme or a plugin built with [wp-app-framework](https://github.com/webcodingcat/wp-app-framework), ready for development:

```sh
composer create-project webcodingcat/wp-app-starter my-theme
```

The directory name is the slug of the project. The installer asks for the rest:

| Question | Notes |
|---|---|
| Kind | `theme`, `child-theme` or `plugin`; a child theme also needs the slug of its parent theme |
| Name | `Theme Name` or `Plugin Name` in the header |
| Composer package name | `vendor/name` |
| Description, author | optional |
| PHP namespace | of `src/`, suggested from the package name (`acme/my-theme` → `Acme\MyTheme`) |
| Namespace | of the framework: the name in front of blocks, REST routes, settings, post types and tables; no default, because content refers to it |
| Tailwind prefix | plugins only, so their classes do not collide with the theme's |
| Options | an example block, PHPStan/PHP-CS-Fixer/ESLint, the GitHub release workflow, browser tests for [wp-test-env](https://github.com/webcodingcat/wp-test-env); each yes by default |
| Theme release | with the release workflow: the parent theme release of a child theme (required), a theme release for a plugin (optional; without, the default theme of WordPress) |

Then it writes the project, installs its dependencies and, if Node 24 or newer is available, builds its scripts and styles; without Node it says how to build later. The project's `README.md` lists its commands. You need Composer; the installer runs in Composer's PHP (8.1 or newer), the project itself needs PHP 8.5.

Without interaction (`--no-interaction`), the answers come from environment variables `WP_APP_STARTER_<KEY>`: `KIND`, `PARENT`, `NAME`, `PACKAGE`, `DESCRIPTION`, `AUTHOR`, `PHP_NAMESPACE`, `NAMESPACE`, `PREFIX`, `EXAMPLE`, `QUALITY`, `CI`, `WITH_THEME`, `E2E` (`yes`/`no` for the options). A required answer without variable is an error:

```sh
WP_APP_STARTER_KIND=plugin WP_APP_STARTER_PACKAGE=acme/shop WP_APP_STARTER_NAMESPACE=shop \
  composer create-project webcodingcat/wp-app-starter shop --no-interaction
```

## Development

The installer is `installer/` (PHP 8.1, no dependencies besides Composer); the files of the projects are in `skeleton/`: `common/`, one directory per kind (`theme/`, `child-theme/`, `plugin/`) and one per option (`example/`, `quality/`, `ci/`, `e2e/`). In their files, placeholders such as `__SLUG__` are replaced, lines `@@if <flag>`/`@@else`/`@@endif` keep or drop lines, and a leading `_` of a name becomes `.` (see `installer/Generator.php`). `composer.json` and `package.json` of the projects are written as data.

The checks run in the tools image of wp-test-env, checked out next to this repository together with wp-app-framework:

```sh
../wp-test-env/bin/tools -w wp-app-starter/tests composer install
../wp-test-env/bin/tools -w wp-app-starter/tests composer test   # PHPUnit
../wp-test-env/bin/tools -w wp-app-starter/tests composer lint   # PHPStan (level max, PHP 8.1) and PHP-CS-Fixer
tests/create my-theme theme                                       # one project in the workspace, without interaction
tests/projects                                                    # a theme, a child theme and a plugin with every option, checked in wp-test-env
```

The CI runs both on every push; the release workflow of wp-app-framework runs it as a gate with the framework of the tag.

## License

GPL-2.0-or-later
