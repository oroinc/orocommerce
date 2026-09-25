<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * What a validated storefront OAuth2 access token says about its subject: the user identifier the token was
 * issued for (a customer user e-mail or an encoded customer visitor identifier) and the organization of the
 * OAuth2 client the token belongs to.
 */
class StorefrontAccessToken
{
    public function __construct(
        public readonly string $userIdentifier,
        public readonly Organization $organization,
    ) {
    }
}
