<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['billingAddress', new OrderAddress()],
            ['saveBillingAddress', true],
            ['shipToBillingAddress', true],
            ['shippingAddress', new OrderAddress()],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['source', new CheckoutSource()],
            ['shippingCost', Price::create(2, 'USD')],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type']
        ];

        $entity = new Checkout();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testSetAccountUser()
    {
        $account = new Account();
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);
        $entity = new Checkout();
        $entity->setAccountUser($accountUser);
        $this->assertSame($account, $entity->getAccount());
    }

    /**
     * @dataProvider getLineItemsDataProvider
     * @param array $expected
     * @param string $sourceInterface
     */
    public function testGetLineItems(array $expected, $sourceInterface)
    {
        $entity = new Checkout();
        if ($sourceInterface) {
            $source = $this->getMockBuilder($sourceInterface)
                ->disableOriginalConstructor()
                ->getMock();
            $source
                ->expects($this->once())
                ->method('getLineItems')
                ->willReturn($expected);

            /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
            $checkoutSource = $this->getMockBuilder('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')
                ->disableOriginalConstructor()
                ->getMock();

            $checkoutSource
                ->expects($this->once())
                ->method('getEntity')
                ->willReturn($source);
            $entity->setSource($checkoutSource);
        }

        $this->assertSame($expected, $entity->getLineItems());
    }

    /**
     * @return array
     */
    public function getLineItemsDataProvider()
    {
        return [
            'without source' => [
                'expected' => [],
                'source' => null,
            ],
            'lineItemsAware' => [
                'expected' => [new \stdClass()],
                'source' => '\Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface',
            ],
            'LineItemsNotPricedAwareInterface' => [
                'expected' => [new \stdClass()],
                'source' => '\Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface',
            ]
        ];
    }

    public function testPostLoad()
    {
        $value = 1;
        $currency = 'USD';

        $item = $this->getEntity(
            'Oro\Bundle\CheckoutBundle\Entity\Checkout',
            [
                'shippingEstimateAmount' => $value,
                'shippingEstimateCurrency' => $currency
            ]
        );

        $item->postLoad();

        $this->assertEquals(Price::create($value, $currency), $item->getShippingCost());
    }

    public function testUpdateShippingEstimate()
    {
        $item = new Checkout();
        $value = 1;
        $currency = 'USD';
        $item->setShippingCost(Price::create($value, $currency));

        $item->updateShippingEstimate();

        $this->assertEquals($value, $item->getShippingCost()->getValue());
        $this->assertEquals($currency, $item->getShippingCost()->getCurrency());
    }
}
