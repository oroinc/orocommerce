<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;

/**
 * @dbIsolation
 */
class FrontendCheckoutOperationsTest extends FrontendActionTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadQuoteCheckoutsData::class,
            ]
        );
    }

    public function testViewOwnCheckout()
    {
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $this->authUser(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD);

        $this->assertExecuteOperation(
            'oro_checkout_view',
            $checkout->getId(),
            Checkout::class,
            ['route' => 'frontend-checkouts-grid']
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('redirectUrl', $data);

        $router = $this->getContainer()->get('router');
        $this->assertEquals(
            $router->generate('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]),
            $data['redirectUrl']
        );
    }

    public function testViewAnotherCheckout()
    {
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_2);

        $this->authUser(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD);

        $this->assertExecuteOperation(
            'oro_checkout_view',
            $checkout->getId(),
            Checkout::class,
            ['route' => 'frontend-checkouts-grid'],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            Response::HTTP_NOT_FOUND
        );


        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($data['success']);
    }

    /**
     * @param string $username
     * @param string $password
     */
    protected function authUser($username, $password)
    {
        $this->initClient([], $this->generateBasicAuthHeader($username, $password));
    }
}
