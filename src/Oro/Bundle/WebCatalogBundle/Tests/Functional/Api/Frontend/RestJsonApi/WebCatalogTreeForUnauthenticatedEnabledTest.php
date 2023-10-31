<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class WebCatalogTreeForUnauthenticatedEnabledTest extends WebCatalogTreeForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
