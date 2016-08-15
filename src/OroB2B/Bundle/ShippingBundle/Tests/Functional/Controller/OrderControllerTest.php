<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\OrderBundle\Entity\Order;

/**
 * @dbIsolation
 * @group CommunityEdition
 */
class OrderControllerTest extends WebTestCase
{
    const ORDER_CLASS = 'OroB2B\Bundle\OrderBundle\Entity\Order';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityManager */
    protected $orderEm;

    /**
     * @param string $gridName
     */
    private function isShippingMethodExcists($gridName)
    {
        $response = $this->client->requestGrid(
            $gridName
        );

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        $this->assertArrayHasKey('shippingMethod', $data[0]);
        $this->assertEquals('Flat Rate', $data[0]['shippingMethod']);
    }


    public function testBackendOrderGrid()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('orders-grid', $crawler->html());

        $this->isShippingMethodExcists('orders-grid');
    }

    public function testFrontentOrderGrid()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('frontend-orders-grid', $crawler->html());

        $this->isShippingMethodExcists('frontend-orders-grid');
    }

    /**
     * @param bool $flush
     * @return Order
     */
    protected function createOrder($flush = true)
    {
        /** @var User $orderUser */
        $orderUser = $this->doctrine
            ->getRepository('OroUserBundle:User')
            ->findOneBy([]);
        if (!$orderUser->getOrganization()) {
            $orderUser->setOrganization(
                $this->doctrine->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
            );
        }
        /** @var AccountUser $accountUser */
        $accountUser = $this->doctrine
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $order = new Order();
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setOwner($orderUser)
            ->setOrganization($orderUser->getOrganization())
            ->setShipUntil(new \DateTime())
            ->setCurrency('EUR')
            ->setPoNumber('PO_NUM')
            ->setSubtotal('1500')
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser)
            ->setShippingMethod('flat_rate');

        $this->orderEm->persist($order);

        if ($flush) {
            $this->orderEm->flush();
        }

        return $order;
    }


    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses'
            ]
        );

        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->orderEm = $this->doctrine->getManagerForClass(self::ORDER_CLASS);

        $this->createOrder();
    }
}
