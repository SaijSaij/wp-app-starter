<?php

/**
 * Plugin Name: __NAME__
@@if description
 * Description: __DESCRIPTION__
@@endif
@@if author
 * Author: __AUTHOR__
@@endif
 * Version: 0.1.0
 * Requires at least: 7.1
 * Requires PHP: 8.5
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: __SLUG__
 */

declare(strict_types=1);

// Release builds ship prefixed dependencies in vendor-prefixed/; source checkouts use Composer's vendor/.
require is_file(__DIR__ . '/vendor-prefixed/autoload.php')
    ? __DIR__ . '/vendor-prefixed/autoload.php'
    : __DIR__ . '/vendor/autoload.php';

WebCodingCat\WpAppFramework\Plugin::boot(__FILE__);
