<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class StartCheckoutForUnauthenticatedEnabledTest extends StartCheckoutForUnauthenticatedTest
{
    use UnauthenticatedEnabledTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
    }
}
