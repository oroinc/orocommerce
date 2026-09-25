<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleTwo;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml declaring the same embedded block (and route) as
 * StorefrontEmbeddedTestBundleOne, so StorefrontEmbeddedConfigurationLoaderTest can prove that the proxied and
 * file routes two bundles contribute for that block are merged and deduplicated.
 */
class StorefrontEmbeddedTestBundleTwo extends Bundle
{
}
