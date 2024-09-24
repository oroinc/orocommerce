<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class OrderAddressForUnauthenticatedEnabledTest extends OrderAddressForUnauthenticatedTest
{
    use UnauthenticatedEnabledTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }
}
