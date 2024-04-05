<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class CatalogTreeForUnauthenticatedEnabledTest extends CatalogTreeForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
