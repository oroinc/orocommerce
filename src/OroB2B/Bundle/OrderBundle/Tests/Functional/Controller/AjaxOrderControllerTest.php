<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;

/**
 * @dbIsolation
 */
class AjaxOrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserAddresses'
            ]
        );
    }

    public function testNewOrderSubtotals()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_create')
        );

        $this->assertTotal($crawler);
    }

    public function testSubtotals()
    {
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_update', ['id' => $order->getId()])
        );

        $this->assertTotal($crawler, $order->getId());
    }

    /**
     * @param Crawler $crawler
     * @param null|int $id
     */
    protected function assertTotal(Crawler $crawler, $id = null)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form->getFormNode()->setAttribute('action', $this->getUrl('orob2b_order_entry_point', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('subtotals', $data['totals']);
        $this->assertArrayHasKey(0, $data['totals']['subtotals']);
        $this->assertArrayHasKey('total', $data['totals']);
    }

    /**
     * @dataProvider getRelatedDataActionDataProvider
     *
     * @param string $account
     * @param string|null $accountUser
     */
    public function testGetRelatedDataAction($account, $accountUser = null)
    {
        /** @var Account $order */
        $accountEntity = $this->getReference($account);

        /** @var AccountUser $order */
        $accountUserEntity = $accountUser ? $this->getReference($accountUser) : null;

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_entry_point'),
            [
                OrderType::NAME => [
                    'account' => $accountEntity->getId(),
                    'accountUser' => $accountUserEntity ? $accountUserEntity->getId() : null
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('billingAddress', $result);
        $this->assertArrayHasKey('shippingAddress', $result);
        $this->assertArrayHasKey('accountPaymentTerm', $result);
        $this->assertArrayHasKey('accountGroupPaymentTerm', $result);
    }

    /**
     * @return array
     */
    public function getRelatedDataActionDataProvider()
    {
        return [
            [
                'account' => 'account.level_1',
                'accountUser' => 'grzegorz.brzeczyszczykiewicz@example.com'
            ],
            [
                'account' => 'account.level_1',
                'accountUser' => null
            ]
        ];
    }
}
