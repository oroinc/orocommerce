<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess;

use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;

/**
 * Declares the paths available to guests when storefront guest access is disabled.
 */
class EmbeddedFragmentAwareGuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /**
     * @param string[] $allowedUrlPatterns Regular expressions of the url paths that are open to guests
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
