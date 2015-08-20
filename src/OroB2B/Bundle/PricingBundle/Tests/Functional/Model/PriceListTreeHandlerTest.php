<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
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
     * @param string $accountReference
     * @param string $expectedPriceListReference
     *
     * @dataProvider accountUserDataProvider
     */
    public function testGetPriceList($accountReference, $expectedPriceListReference)
    {
        $accountUser = new AccountUser();
        $accountUser->setAccount($this->getAccount($accountReference));

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
            'get PriceList from account' => ['account.level_1.2', 'price_list_2'],
            'get PriceList from parent' => ['account.level_1.2.1', 'price_list_2'],
            'get PriceList from parents parent' => ['account.level_1.2.1.1', 'price_list_2'],
            'get PriceList from group' => ['account.level_1.3', 'price_list_1'],
            'get PriceList from parent group' => ['account.level_1.3.1', 'price_list_1'],
            'get PriceList from parents parent group' => ['account.level_1.3.1.1', 'price_list_1'],
            'get PriceList from website' => ['account.level_1.4', 'price_list_1'],
        ];
    }

    public function testDefaultWithoutAccountUser()
    {
        $this->websiteManager->expects($this->once())->method('getCurrentWebsite')->willReturn(null);

        $this->assertTrue($this->handler->getPriceList()->isDefault());
    }

    public function testDefaultWithoutAccount()
    {
        $accountUser = new AccountUser();

        $this->websiteManager->expects($this->once())->method('getCurrentWebsite');

        $this->assertTrue($this->handler->getPriceList($accountUser)->isDefault());
    }

    public function testDefaultIfNotFound()
    {
        $accountUser = new AccountUser();
        $accountUser->setAccount($this->getAccount('account.level_1'));

        $this->websiteManager->expects($this->once())->method('getCurrentWebsite')->willReturn(null);

        $this->assertTrue($this->handler->getPriceList($accountUser)->isDefault());
    }

    /**
     * @param string $reference
     * @return Account
     */
    protected function getAccount($reference)
    {
        return $this->getReference($reference);
    }
}
