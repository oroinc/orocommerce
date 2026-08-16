<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class BrandForUnauthenticatedEnabledTest extends BrandForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
