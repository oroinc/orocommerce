<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
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
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListContextDataConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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
     * @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shoppingListTotalManager;

    /**
     * @var ShoppingListContextDataConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->criteriaDataProvider = $this->createMock(CriteriaDataProvider::class);
        $this->lineItemsConverter = $this->createMock(LineItemsToDiscountLineItemsConverter::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $this->converter = new ShoppingListContextDataConverter(
            $this->criteriaDataProvider,
            $this->lineItemsConverter,
            $this->userCurrencyManager,
            $this->scopeManager,
            $this->shoppingListTotalManager
        );
    }

    public function testSupportsForWrongEntity(): void
    {
        $entity = new \stdClass();
        $this->assertFalse($this->converter->supports($entity));
    }

    public function testSupports(): void
    {
        $entity = new ShoppingList();
        $this->assertTrue($this->converter->supports($entity));
    }

    public function testGetContextDataWhenThrowsException(): void
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Entity "stdClass" is not supported.');

        $this->converter->getContextData($entity);
    }

    public function testGetContextData(): void
    {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $website = new Website();
        $lineItem = new LineItem();

        $subtotalAmount = 100.0;
        $subtotal = new Subtotal();
        $subtotal->setAmount($subtotalAmount);

        $entity = new ShoppingList();
        $entity->setCustomerUser($customerUser);
        $entity->addLineItem($lineItem);
        $entity->setSubtotal($subtotal);

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

        $subtotal = $this->createMock(Subtotal::class);
        $subtotal->expects($this->once())
            ->method('getAmount')
            ->willReturn($subtotalAmount);

        $shoppingListTotal = $this->createMock(ShoppingListTotal::class);
        $shoppingListTotal->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $currency = $this->getCurrency();
        $this->shoppingListTotalManager->expects($this->once())
            ->method('getShoppingListTotalForCurrency')
            ->with($entity, $currency, false)
            ->willReturn($shoppingListTotal);

        $this->assertEquals(
            [
                ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
                ContextDataConverterInterface::CUSTOMER => $customer,
                ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
                ContextDataConverterInterface::LINE_ITEMS => $this->getDiscountLineItems([$lineItem]),
                ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
                ContextDataConverterInterface::CURRENCY => $this->getCurrency(),
                ContextDataConverterInterface::CRITERIA => $this->getScopeCriteria($customer, $customerGroup, $website),
            ],
            $this->converter->getContextData($entity)
        );
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

    private function getCurrency(): string
    {
        $currency = 'USD';
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        return $currency;
    }

    private function getScopeCriteria(Customer $customer, CustomerGroup $customerGroup, Website $website): ScopeCriteria
    {
        $scopeContext = [
            'customer' => $customer,
            'customerGroup' => $customerGroup,
            'website' => $website
        ];
        $scopeCriteria = new ScopeCriteria([], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('promotion', $scopeContext)
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }
}
