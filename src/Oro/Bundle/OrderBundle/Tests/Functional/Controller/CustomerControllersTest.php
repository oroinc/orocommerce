<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllersTest extends WebTestCase
{
    /** @var $customerUser CustomerUser */
    protected $customerUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            ]
        );
        $manager = $this->client->getContainer()->get('doctrine')->getManagerForClass(
            'OroCustomerBundle:CustomerUser'
        );
        $this->customerUser = $manager->getRepository('OroCustomerBundle:CustomerUser')->findOneBy(
            ['username' => LoadOrders::ACCOUNT_USER]
        );
    }

    public function testCustomerViewAndGrid()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_view', ['id' => $this->customerUser->getCustomer()->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        static::assertStringContainsString('customer-orders-grid', $content);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'customer-orders-grid',
                'customer-orders-grid[customer_id]' => $this->customerUser->getCustomer()->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }

    public function testCustomerUserViewAndGrid()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_view', ['id' => $this->customerUser->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        static::assertStringContainsString('customer-user-orders-grid', $content);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'customer-user-orders-grid',
                'customer-user-orders-grid[customer_user_id]' => $this->customerUser->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }

    protected function checkDatagridResponse(Response $response)
    {
        $result = $this->getJsonResponseContent($response, 200);
        static::assertStringContainsString(\sprintf('USD%.2F', LoadOrders::SUBTOTAL), $result['data'][0]['subtotal']);
    }
}
