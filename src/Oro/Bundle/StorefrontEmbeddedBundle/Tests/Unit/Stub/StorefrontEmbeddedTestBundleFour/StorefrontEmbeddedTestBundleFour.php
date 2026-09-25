<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleFour;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml mapping the same embedded block as
 * StorefrontEmbeddedTestBundleThree to a different route, so StorefrontEmbeddedConfigurationLoaderTest can prove
 * the loader rejects an embedded block id that is not unique across bundles.
 */
class StorefrontEmbeddedTestBundleFour extends Bundle
{
}
