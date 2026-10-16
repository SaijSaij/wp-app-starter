<?php

declare(strict_types=1);

namespace __PHP_NAMESPACE__\Block\Teaser;

use Saij\WpAppFramework\Block\Block;
use Saij\WpAppFramework\Block\BlockCategory;
use Saij\WpAppFramework\Twig\TemplateRenderer;

/**
 * An example block: a title and a text edited in the editor canvas, and a switch in the inspector.
 * Registered as __WP_NAMESPACE__/teaser; delete the directory when you no longer need it.
 */
#[Block('teaser', title: 'Teaser', category: BlockCategory::Text, icon: 'text', description: 'A title and a text.')]
final readonly class Teaser
{
    public function __construct(private TemplateRenderer $renderer) {}

    public function render(TeaserAttributes $attrs): string
    {
        return $this->renderer->render('Block/Teaser/Teaser.twig', ['attrs' => $attrs]);
    }
}
