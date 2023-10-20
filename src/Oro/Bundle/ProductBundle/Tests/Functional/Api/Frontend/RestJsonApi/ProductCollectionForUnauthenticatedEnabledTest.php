<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class ProductCollectionForUnauthenticatedEnabledTest extends ProductCollectionForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
