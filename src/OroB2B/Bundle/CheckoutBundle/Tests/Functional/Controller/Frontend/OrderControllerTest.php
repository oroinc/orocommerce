<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
                'OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures\LoadAlternativeCheckouts',
                'OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckouts'
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $response = $this->requestFrontendGrid(
            [
                'gridName' => 'frontend-checkouts-grid',
            ]
        );
    }
}
