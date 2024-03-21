<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class OrderLineItemForUnauthenticatedEnabledTest extends OrderLineItemForUnauthenticatedTest
{
    use UnauthenticatedEnabledTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }
}
