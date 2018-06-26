<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\CriteriaDataProvider;
use Oro\Bundle\PromotionBundle\Context\ShoppingListContextDataConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ShoppingListContextDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CriteriaDataProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $criteriaDataProvider;

    /**
     * @var LineItemsToDiscountLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemsConverter;

    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userCurrencyManager;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var LineItemNotPricedSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemNotPricedSubtotalProvider;

    /**
     * @var ShoppingListContextDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->criteriaDataProvider = $this->createMock(CriteriaDataProvider::class);
        $this->lineItemsConverter = $this->createMock(LineItemsToDiscountLineItemsConverter::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->lineItemNotPricedSubtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);
        $this->converter = new ShoppingListContextDataConverter(
            $this->criteriaDataProvider,
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
        $website = new Website();
        $lineItem = new LineItem();
        $entity = new ShoppingList();
        $entity->setCustomerUser($customerUser);
        $entity->addLineItem($lineItem);

        $subtotalAmount = 100.0;
        $subtotal = new Subtotal();
        $subtotal->setAmount($subtotalAmount);

        $this->criteriaDataProvider->expects($this->once())
            ->method('getWebsite')
            ->with($entity)
            ->willReturn($website);
        $this->criteriaDataProvider->expects($this->once())
            ->method('getCustomer')
            ->with($entity)
            ->willReturn($customer);
        $this->criteriaDataProvider->expects($this->once())
            ->method('getCustomerUser')
            ->with($entity)
            ->willReturn($customerUser);
        $this->criteriaDataProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($entity)
            ->willReturn($customerGroup);
        $discountLineItems = $this->getDiscountLineItems([$lineItem]);
        $currency = $this->getCurrency();
        $scopeCriteria = $this->getScopeCriteria($customer, $customerGroup, $website);

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
     * @param Customer $customer
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return ScopeCriteria
     */
    private function getScopeCriteria(Customer $customer, CustomerGroup $customerGroup, Website $website): ScopeCriteria
    {
        $scopeContext = [
            'customer' => $customer,
            'customerGroup' => $customerGroup,
            'website' => $website
        ];
        $scopeCriteria = new ScopeCriteria([], []);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('promotion', $scopeContext)
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }
}
