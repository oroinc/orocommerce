<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\WorkflowDefinitionCheckoutTestCase as BaseTest;

/**
 * @dbIsolation
 */
class WorkflowDefinitionControllerTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testCheckoutWorkflowViewPage()
    {
        $this->assertCheckoutWorkflowCorrectViewPage(
            'b2b_flow_alternative_checkout',
            'Alternative Checkout',
            'b2b_checkout_flow'
        );
    }
}
