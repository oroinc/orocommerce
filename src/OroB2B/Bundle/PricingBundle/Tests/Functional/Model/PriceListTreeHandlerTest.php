<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;

/**
 * @dbIsolation
 */
class PriceListTreeHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
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

        $this->assertEquals(
            $this->getHandler()->getPriceList($accountUser)->getId(),
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
            'get PriceList from parent' => [],
            'get PriceList from parents parent' => [],
            'get PriceList from group' => [],
            'get PriceList from parent group' => [],
            'get PriceList from parents parent group' => [],
            'get PriceList from website' => [],
        ];
    }

    public function testDefaultWithoutAccount()
    {
        $accountUser = new AccountUser();

        $this->assertTrue($this->getHandler()->getPriceList($accountUser)->isDefault());
    }

    public function testDefaultIfNotFound()
    {
        $accountUser = new AccountUser();
        $accountUser->setCustomer($this->getCustomer('customer.level_1'));

        $this->assertTrue($this->getHandler()->getPriceList($accountUser)->isDefault());
    }

    /**
     * @param string $reference
     * @return Customer
     */
    protected function getCustomer($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return PriceListTreeHandler
     */
    protected function getHandler()
    {
        return $this->client->getContainer()->get('orob2b_pricing.model.price_list_tree_handler');
    }
}
