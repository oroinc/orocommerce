<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class OrderAddressForUnauthenticatedEnabledTest extends OrderAddressForUnauthenticatedTest
{
    use UnauthenticatedEnabledTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }
}
