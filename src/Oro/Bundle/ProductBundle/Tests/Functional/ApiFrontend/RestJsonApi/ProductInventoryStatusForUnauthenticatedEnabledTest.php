<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class ProductInventoryStatusForUnauthenticatedEnabledTest extends ProductInventoryStatusForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
