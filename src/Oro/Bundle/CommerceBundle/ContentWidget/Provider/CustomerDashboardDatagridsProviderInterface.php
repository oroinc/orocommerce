<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

/**
 * Abstraction for providers that return storefront datagrids for customer dashboard content widget.
 */
interface CustomerDashboardDatagridsProviderInterface
{
    public function getDatagrids(): array;
}
