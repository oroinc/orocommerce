<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * @dbIsolation
 */
class PriceListTreeHandlerTest extends WebTestCase
{
    /**
     * @var PriceListTreeHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsiteManager
     */
    protected $websiteManager;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);

        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new PriceListTreeHandler(
            $this->getContainer()->get('doctrine'),
            $this->websiteManager,
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
    }

    /**
     * @param string $customerReference
     * @param string $expectedPriceListReference
     *
     * @dataProvider accountUserDataProvider
     */
    public function testGetPriceList($customerReference, $expectedPriceListReference)
    {
        $accountUser = new AccountUser();
        $accountUser->setCustomer($this->getCustomer($customerReference));

        $this->websiteManager->expects($this->any())->method('getCurrentWebsite')
            ->willReturn($this->getReference('US'));

        $this->assertEquals(
            $this->handler->getPriceList($accountUser)->getId(),
            $this->getReference($expectedPriceListReference)->getId()
        );
    }

    /**
     * @return array
     */
    public function accountUserDataProvider()
    {
        return [
            'get PriceList from customer' => ['customer.level_1.2', 'price_list_2'],
            'get PriceList from parent' => ['customer.level_1.2.1', 'price_list_2'],
            'get PriceList from parents parent' => ['customer.level_1.2.1.1', 'price_list_2'],
            'get PriceList from group' => ['customer.level_1.3', 'price_list_1'],
            'get PriceList from parent group' => ['customer.level_1.3.1', 'price_list_1'],
            'get PriceList from parents parent group' => ['customer.level_1.3.1.1', 'price_list_1'],
            'get PriceList from website' => ['customer.level_1.4', 'price_list_1'],
        ];
    }

    public function testDefaultWithoutAccount()
    {
        $accountUser = new AccountUser();

        $this->websiteManager->expects($this->never())->method('getCurrentWebsite');

        $this->assertTrue($this->handler->getPriceList($accountUser)->isDefault());
    }

    public function testDefaultIfNotFound()
    {
        $accountUser = new AccountUser();
        $accountUser->setCustomer($this->getCustomer('customer.level_1'));

        $this->websiteManager->expects($this->any())->method('getCurrentWebsite')->willReturn(null);

        $this->assertTrue($this->handler->getPriceList($accountUser)->isDefault());
    }

    /**
     * @param string $reference
     * @return Customer
     */
    protected function getCustomer($reference)
    {
        return $this->getReference($reference);
    }
}
