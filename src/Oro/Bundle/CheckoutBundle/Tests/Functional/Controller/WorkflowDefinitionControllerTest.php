<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\WorkflowDefinitionCheckoutTestCase as BaseTest;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadTranslations;

class WorkflowDefinitionControllerTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testCheckoutWorkflowViewPage()
    {
        $this->assertCheckoutWorkflowCorrectViewPage(
            'b2b_flow_checkout',
            'Checkout',
            'b2b_checkout_flow'
        );
    }
}
