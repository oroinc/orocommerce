<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\Provider\AccessibilityPageProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for help page and landing pages:
 *   - oro_accessibility_page_url - Get help page URL from system configuration
 *   - oro_accessibility_page_title - Get help page title from system configuration
 */
class AccessibilityPageExtension extends AbstractExtension
{
    public function __construct(
        private AccessibilityPageProvider $accessibilityPageProvider
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_accessibility_page_url', [$this, 'getAccessibilityPageUrl']),
            new TwigFunction('oro_accessibility_page_title', [$this, 'getAccessibilityPageTitle']),
        ];
    }

    /**
     * Get the help page URL from the system configuration.
     *
     * @return string|null URL or null if not configured
     */
    public function getAccessibilityPageUrl(): ?string
    {
        return $this->accessibilityPageProvider->getAccessibilityPageUrl();
    }

    /**
     * Get the help page title from system configuration.
     */
    public function getAccessibilityPageTitle(): string
    {
        return $this->accessibilityPageProvider->getAccessibilityPageTitle();
    }
}
