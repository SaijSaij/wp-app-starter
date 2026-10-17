<?php

declare(strict_types=1);

namespace __PHP_NAMESPACE__\Block\Teaser;

use WebCodingCat\WpAppFramework\Value\Label;
use WebCodingCat\WpAppFramework\Value\PlainText;
use WebCodingCat\WpAppFramework\Value\RichText;

/**
 * The attributes of the teaser: each constructor parameter is a block attribute.
 */
final readonly class TeaserAttributes
{
    public function __construct(
        #[PlainText]
        public string $title = '',
        #[RichText(allowedFormats: ['core/bold', 'core/italic', 'core/link'])]
        public string $text = '',
        #[Label('Highlighted')]
        public bool $highlighted = false,
    ) {}
}
