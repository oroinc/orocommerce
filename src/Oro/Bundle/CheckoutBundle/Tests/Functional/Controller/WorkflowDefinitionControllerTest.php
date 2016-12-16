<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\WorkflowDefinitionCheckoutTestCase as BaseTest;

/**
 * @dbIsolation
 */
class WorkflowDefinitionControllerTest extends BaseTest
{
    public function testCheckoutWorkflowViewPage()
    {
        $this->markTestSkipped("Skipped until BAP-13043 gets resolved!");
        $this->assertCheckoutWorkflowCorrectViewPage(
            'b2b_flow_checkout',
            'Checkout',
            'b2b_checkout_flow'
        );
    }
}
