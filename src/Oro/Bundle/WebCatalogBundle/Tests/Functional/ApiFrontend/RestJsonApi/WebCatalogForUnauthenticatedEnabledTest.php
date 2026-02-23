<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class WebCatalogForUnauthenticatedEnabledTest extends WebCatalogForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
