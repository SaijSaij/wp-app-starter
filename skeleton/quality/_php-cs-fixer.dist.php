<?php

declare(strict_types=1);

// Code style: PER Coding Style, plus sorted and no unused imports (the rules of wp-app-framework).
return new PhpCsFixer\Config()
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src'])
            ->append([__DIR__ . '/__BOOT_FILE__', __FILE__]),
    )
    ->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer-__SLUG__.cache');
