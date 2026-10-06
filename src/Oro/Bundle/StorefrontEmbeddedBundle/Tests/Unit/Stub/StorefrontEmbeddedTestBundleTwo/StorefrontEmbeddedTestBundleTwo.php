<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleTwo;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml defining the embedded fragment of
 * StorefrontEmbeddedTestBundleOne in the short form, so StorefrontEmbeddedConfigurationLoaderTest can prove that
 * the bundle loaded last resets the layout block ids to the default instead of keeping the ones defined earlier.
 */
class StorefrontEmbeddedTestBundleTwo extends Bundle
{
}
