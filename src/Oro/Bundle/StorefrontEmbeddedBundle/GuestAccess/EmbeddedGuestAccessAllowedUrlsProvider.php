<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess;

use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;

/**
 * Opens the theme assets list of this bundle to guests when storefront guest access is disabled.
 */
class EmbeddedGuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /**
     * @param string[] $allowedUrlPatterns regular expressions of the url paths that are open to guests
     */
    public function __construct(private readonly array $allowedUrlPatterns)
    {
    }

    #[\Override]
    public function getAllowedUrlsPatterns(): array
    {
        return $this->allowedUrlPatterns;
    }
}
