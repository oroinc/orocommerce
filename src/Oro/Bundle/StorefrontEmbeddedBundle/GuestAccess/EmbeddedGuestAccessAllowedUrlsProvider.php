<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess;

use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;

/**
 * Names the urls of this bundle a guest may call while storefront guest access is disabled: the session
 * bootstrap, which is what turns an OAuth2 token into a session in the first place, and the theme assets list,
 * which names public static files only. Fragment urls stay closed, like the pages they embed.
 */
class EmbeddedGuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /**
     * @param string[] $allowedUrlPatterns regular expressions of the url paths open to guests
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
