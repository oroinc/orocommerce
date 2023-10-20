<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class CategoryForUnauthenticatedEnabledTest extends CategoryForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
