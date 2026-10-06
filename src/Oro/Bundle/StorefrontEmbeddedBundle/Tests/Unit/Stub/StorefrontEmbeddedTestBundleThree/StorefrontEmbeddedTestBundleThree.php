<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleThree;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml defining the embedded fragment of
 * StorefrontEmbeddedTestBundleOne in the expanded form with a different route and layout block ids, so
 * StorefrontEmbeddedConfigurationLoaderTest can prove that the bundle loaded last replaces the whole definition.
 */
class StorefrontEmbeddedTestBundleThree extends Bundle
{
}
