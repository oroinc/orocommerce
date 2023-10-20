<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class CatalogTreeForUnauthenticatedEnabledTest extends CatalogTreeForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
