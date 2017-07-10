<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\ShoppingListContextDataConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListContextDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $relationsProvider;

    /**
     * @var LineItemsToDiscountLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsConverter;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userCurrencyManager;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    /**
     * @var LineItemNotPricedSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemNotPricedSubtotalProvider;

    /**
     * @var ShoppingListContextDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->lineItemsConverter = $this->createMock(LineItemsToDiscountLineItemsConverter::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->lineItemNotPricedSubtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);
        $this->converter = new ShoppingListContextDataConverter(
            $this->relationsProvider,
            $this->lineItemsConverter,
            $this->userCurrencyManager,
            $this->scopeManager,
            $this->lineItemNotPricedSubtotalProvider
        );
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->converter->supports($entity));
    }

    public function testSupports()
    {
        $entity = new ShoppingList();
        $this->assertTrue($this->converter->supports($entity));
    }

    public function testGetContextDataWhenThrowsException()
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Entity "stdClass" is not supported.');

        $this->converter->getContextData($entity);
    }

    public function testGetContextData()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $lineItem = new LineItem();
        $entity = new ShoppingList();
        $entity->setCustomerUser($customerUser);
        $entity->addLineItem($lineItem);

        $subtotalAmount = 100.0;
        $subtotal = new Subtotal();
        $subtotal->setAmount($subtotalAmount);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($customerUser)
            ->willReturn($customer);
        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn($customerGroup);
        $discountLineItems = $this->getDiscountLineItems([$lineItem]);
        $currency = $this->getCurrency();
        $scopeCriteria = $this->getScopeCriteria();

        $this->lineItemNotPricedSubtotalProvider->expects($this->any())
            ->method('getSubtotalByCurrency')
            ->with($entity, $currency)
            ->willReturn($subtotal);

        $expectedData = [
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
            ContextDataConverterInterface::LINE_ITEMS => $discountLineItems,
            ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria,
        ];
        $this->assertEquals($expectedData, $this->converter->getContextData($entity));
    }

    public function testGetContextDataWhenCustomerGroupIsNotFounded()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $customerUser = new CustomerUser();
        $lineItem = new LineItem();
        $entity = new ShoppingList();
        $entity->setCustomerUser($customerUser);
        $entity->setCustomer($customer);
        $entity->addLineItem($lineItem);

        $subtotalAmount = 100.0;
        $subtotal = new Subtotal();
        $subtotal->setAmount($subtotalAmount);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($customerUser)
            ->willReturn($customer);
        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn(null);
        $discountLineItems = $this->getDiscountLineItems([$lineItem]);
        $currency = $this->getCurrency();
        $scopeCriteria = $this->getScopeCriteria();

        $this->lineItemNotPricedSubtotalProvider->expects($this->any())
            ->method('getSubtotalByCurrency')
            ->with($entity, $currency)
            ->willReturn($subtotal);

        $expectedData = [
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
            ContextDataConverterInterface::LINE_ITEMS => $discountLineItems,
            ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria,
        ];
        $this->assertEquals($expectedData, $this->converter->getContextData($entity));
    }

    /**
     * @param LineItem[]|array $lineItems
     * @return DiscountLineItem[]|array
     */
    private function getDiscountLineItems(array $lineItems): array
    {
        $discountLineItems = [new DiscountLineItem()];
        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with($lineItems)
            ->willReturn($discountLineItems);

        return $discountLineItems;
    }

    /**
     * @return string
     */
    private function getCurrency(): string
    {
        $currency = 'USD';
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        return $currency;
    }

    /**
     * @return ScopeCriteria
     */
    private function getScopeCriteria(): ScopeCriteria
    {
        $scopeCriteria = new ScopeCriteria([], []);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('promotion')
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }
}
