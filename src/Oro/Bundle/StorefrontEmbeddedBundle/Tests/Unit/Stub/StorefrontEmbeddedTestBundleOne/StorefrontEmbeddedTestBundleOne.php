<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleOne;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml defining an embedded fragment in the expanded form with layout
 * block ids that differ from the default (the fragment name). StorefrontEmbeddedTestBundleTwo and
 * StorefrontEmbeddedTestBundleThree define the same fragment again, so StorefrontEmbeddedConfigurationLoaderTest can
 * prove that the bundle loaded last replaces the whole definition instead of keeping or adding to these ids.
 */
class StorefrontEmbeddedTestBundleOne extends Bundle
{
}
