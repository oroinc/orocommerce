<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\ProductPriceScopeCriteriaFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceScopeCriteriaFactoryTest extends TestCase
{
    private CustomerUserProvider|MockObject $customerUserProvider;
    private CustomerUserRelationsProvider|MockObject $customerRelationsProvider;
    private ProductPriceScopeCriteriaFactoryInterface|MockObject $inner;
    private ProductPriceScopeCriteriaFactory $factory;

    protected function setUp(): void
    {
        $this->customerUserProvider = $this->createMock(CustomerUserProvider::class);
        $this->inner = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->factory = new ProductPriceScopeCriteriaFactory(
            $this->customerUserProvider,
            $this->inner
        );

        $this->customerRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->factory->setCustomerUserRelationsProvider($this->customerRelationsProvider);
    }

    public function testCreate(): void
    {
        $website = $this->createMock(Website::class);
        $customer = $this->createMock(Customer::class);
        $context = new \stdClass();
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $data = [];

        $this->inner->expects(self::once())
            ->method('create')
            ->with($website, $customer, $context, $data)
            ->willReturn($criteria);

        $this->assertSame($criteria, $this->factory->create($website, $customer, $context, $data));
    }

    public function testCreateByContextWithUnsupportedContext(): void
    {
        $context = new \stdClass();
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->inner->expects(self::once())
            ->method('createByContext')
            ->with($context, [])
            ->willReturn($criteria);

        $this->customerUserProvider
            ->expects(self::never())
            ->method('getLoggedUser');

        $this->customerRelationsProvider
            ->expects(self::never())
            ->method('getCustomerIncludingEmpty');

        $result = $this->factory->createByContext($context);
        $this->assertSame($criteria, $result);
    }

    public function testCreateByContextWithShoppingList(): void
    {
        $context = $this->createMock(ShoppingList::class);
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $data = [];

        $this->inner->expects(self::once())
            ->method('createByContext')
            ->with($context, $data)
            ->willReturn($criteria);

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        $this->customerRelationsProvider->expects(self::once())
            ->method('getCustomerIncludingEmpty')
            ->with($customerUser)
            ->willReturn($customer);

        $criteria->expects(self::once())
            ->method('setCustomer')
            ->with($customer);

        $result = $this->factory->createByContext($context, $data);
        $this->assertSame($criteria, $result);
    }

    public function testCreateByContextWithShoppingListWithoutCustomer(): void
    {
        $context = $this->createMock(ShoppingList::class);
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $data = [];

        $this->inner->expects(self::once())
            ->method('createByContext')
            ->with($context, $data)
            ->willReturn($criteria);

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        $this->customerRelationsProvider->expects(self::once())
            ->method('getCustomerIncludingEmpty')
            ->with($customerUser)
            ->willReturn(null);

        $criteria->expects(self::never())
            ->method('setCustomer');

        $result = $this->factory->createByContext($context, $data);
        $this->assertSame($criteria, $result);
    }

    public function testCreateByContextWithShoppingListWithoutCustomerHolder(): void
    {
        $context = $this->createMock(ShoppingList::class);
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $data = [];

        $this->inner->expects(self::once())
            ->method('createByContext')
            ->with($context, $data)
            ->willReturn($criteria);

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn(null);

        $context->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $this->customerRelationsProvider->expects(self::once())
            ->method('getCustomerIncludingEmpty')
            ->with($customerUser)
            ->willReturn(null);

        $criteria->expects(self::never())
            ->method('setCustomer');

        $result = $this->factory->createByContext($context, $data);
        $this->assertSame($criteria, $result);
    }

    public function testCreateByContextWithProductLineItemsHolderDTO(): void
    {
        $customer = $this->createMock(Customer::class);
        $customerUser = new CustomerUser();
        $shoppingList = (new ShoppingList())
            ->setCustomerUser($customerUser);
        $product = (new ProductStub())
            ->setId(43);
        $productUnit = (new ProductUnit())
            ->setCode('item');
        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnit)
            ->setShoppingList($shoppingList);

        $context = (new ProductLineItemsHolderDTO())
            ->setLineItems(new ArrayCollection([10 => $lineItem]));
        $criteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $data = [];

        $this->inner->expects(self::once())
            ->method('createByContext')
            ->with($context, $data)
            ->willReturn($criteria);


        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        $this->customerRelationsProvider->expects(self::once())
            ->method('getCustomerIncludingEmpty')
            ->with($shoppingList->getCustomerUser())
            ->willReturn($customer);

        $criteria->expects(self::once())
            ->method('setCustomer')
            ->with($customer);

        $result = $this->factory->createByContext($context, $data);
        $this->assertSame($criteria, $result);
    }
}
