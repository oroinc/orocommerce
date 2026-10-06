<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleOne;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ships Resources/config/oro/storefront_embedded.yml declaring the same embedded block (and route) as
 * StorefrontEmbeddedTestBundleTwo, so StorefrontEmbeddedConfigurationLoaderTest can prove that two bundles may
 * map one block to the same route. StorefrontEmbeddedTestBundleThree
 * maps the same block to a different route, so the loader test can also prove that the conflict is rejected.
 */
class StorefrontEmbeddedTestBundleOne extends Bundle
{
}
