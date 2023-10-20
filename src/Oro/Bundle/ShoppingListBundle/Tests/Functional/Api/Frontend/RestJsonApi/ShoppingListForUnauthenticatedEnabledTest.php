<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\UnauthenticatedEnabledTestTrait;

class ShoppingListForUnauthenticatedEnabledTest extends ShoppingListForUnauthenticatedTest
{
    use UnauthenticatedEnabledTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }
}
