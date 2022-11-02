<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

class OrderLineItemsGridFrontendTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCustomerVisitors::class
        ]);
    }

    public function testDatagridWIthCustomerVisitor()
    {
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
                base64_encode(\json_encode([$visitor->getId(), $visitor->getSessionId()])),
                time() + 60
            )
        );

        $gridResponse = $this->client->requestFrontendGrid(
            ['gridName' => 'order-line-items-grid-frontend'],
            ['order-line-items-grid-frontend[order_id]' => 1],
            true
        );
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $gridResponse->getStatusCode());
    }

    public function testDatagridWIthCustomerUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $gridResponse = $this->client->requestFrontendGrid(
            ['gridName' => 'order-line-items-grid-frontend'],
            ['order-line-items-grid-frontend[order_id]' => 1],
            true
        );
        self::assertEquals(Response::HTTP_OK, $gridResponse->getStatusCode());
    }
}
