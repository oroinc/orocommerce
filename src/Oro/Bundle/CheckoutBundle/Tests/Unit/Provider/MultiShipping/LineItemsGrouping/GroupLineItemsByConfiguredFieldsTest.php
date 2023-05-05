<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItemsGrouping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupLineItemsByConfiguredFields;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class GroupLineItemsByConfiguredFieldsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var GroupLineItemsByConfiguredFields */
    private $groupedLineItemsProvider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->groupedLineItemsProvider = new GroupLineItemsByConfiguredFields(
            $this->configProvider,
            $this->propertyAccessor,
            $this->doctrineHelper
        );
    }

    public function testGetGroupedLineItems()
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([
            new CheckoutLineItem(), new CheckoutLineItem(), new CheckoutLineItem(), new CheckoutLineItem()
        ]));

        $this->configProvider->expects($this->once())
            ->method('getGroupLineItemsByField')
            ->willReturn('product.owner');

        $this->propertyAccessor->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(new BusinessUnit(), new BusinessUnit(), new BusinessUnit(), null);

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getSingleEntityIdentifier')
            ->willReturnOnConsecutiveCalls(1, 2, 1);

        $result = $this->groupedLineItemsProvider->getGroupedLineItems($checkout);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);
        $this->assertArrayHasKey('product.owner:0', $result);

        $this->assertCount(2, $result['product.owner:1']);
        $this->assertCount(1, $result['product.owner:2']);
        $this->assertCount(1, $result['product.owner:0']);
    }

    public function testGetGroupedLineItemsWithFreeFromProduct()
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([
            new CheckoutLineItem(), new CheckoutLineItem(), new CheckoutLineItem(),
        ]));

        $this->configProvider->expects($this->once())
            ->method('getGroupLineItemsByField')
            ->willReturn('product.owner');

        $this->propertyAccessor->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                new BusinessUnit(),
                $this->throwException(new UnexpectedTypeException(
                    0,
                    $this->createMock(PropertyPathInterface::class),
                    2
                )),
                new BusinessUnit()
            );

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getSingleEntityIdentifier')
            ->willReturnOnConsecutiveCalls(1, 1);

        $result = $this->groupedLineItemsProvider->getGroupedLineItems($checkout);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('other-items', $result);

        $this->assertCount(2, $result['product.owner:1']);
        $this->assertCount(1, $result['other-items']);
    }

    public function testGetGroupedLineItemsWithScalarValuesReturnedByPropertyAccessor()
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([
            new CheckoutLineItem(), new CheckoutLineItem(), new CheckoutLineItem()
        ]));

        $this->configProvider->expects($this->once())
            ->method('getGroupLineItemsByField')
            ->willReturn('product.sku');

        $this->propertyAccessor->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('sku-1', 'sku-2', 'sku-3');

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');


        $result = $this->groupedLineItemsProvider->getGroupedLineItems($checkout);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('product.sku:sku-1', $result);
        $this->assertArrayHasKey('product.sku:sku-2', $result);
        $this->assertArrayHasKey('product.sku:sku-3', $result);
    }

    public function testGetGroupedLineItemsWithCachedValues()
    {
        $checkout = new Checkout();
        $cachedGroupedLineItems[spl_object_hash($checkout)] = [
            'product.owner:1' => [new CheckoutLineItem()],
            'product.owner:2' => [new CheckoutLineItem(), new CheckoutLineItem()]
        ];

        ReflectionUtil::setPropertyValue(
            $this->groupedLineItemsProvider,
            'cachedGroupedLineItems',
            $cachedGroupedLineItems
        );

        $this->configProvider->expects($this->never())
            ->method('getGroupLineItemsByField');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $result = $this->groupedLineItemsProvider->getGroupedLineItems($checkout);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $this->assertCount(1, $result['product.owner:1']);
        $this->assertCount(2, $result['product.owner:2']);
    }
}
