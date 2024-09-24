<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class GroupLineItemHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var GroupLineItemHelper */
    private $groupLineItemHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) {
                return $entity->getId();
            });

        $this->groupLineItemHelper = new GroupLineItemHelper(
            $this->configProvider,
            PropertyAccess::createPropertyAccessor(),
            $doctrineHelper
        );
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    private function getLineItem(int $id): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);

        return $lineItem;
    }

    private function setLineItemProduct(CheckoutLineItem $lineItem, ?Organization $organization): void
    {
        $product = new Product();
        if (null !== $organization) {
            $product->setOrganization($organization);
        }
        $lineItem->setProduct($product);
    }

    public function testGetGroupedLineItemsWhenLineItemIsGroupedByAssociation(): void
    {
        $organization1 = $this->getOrganization(10);
        $lineItem1 = $this->getLineItem(1);
        $this->setLineItemProduct($lineItem1, $organization1);
        $lineItem2 = $this->getLineItem(2);
        $this->setLineItemProduct($lineItem2, $organization1);
        $lineItem3 = $this->getLineItem(3);
        $this->setLineItemProduct($lineItem3, $this->getOrganization(20));
        $lineItem4 = $this->getLineItem(4);
        $this->setLineItemProduct($lineItem4, null);
        $lineItem5 = $this->getLineItem(5);
        $lineItem5->setProductSku('FREE_FORM_PRODUCT');

        self::assertEquals(
            [
                'product.organization:10' => [$lineItem1, $lineItem2],
                'product.organization:20' => [$lineItem3],
                'product.organization:0'  => [$lineItem4],
                'other-items'             => [$lineItem5]
            ],
            $this->groupLineItemHelper->getGroupedLineItems(
                new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4, $lineItem5]),
                'product.organization'
            )
        );
    }

    public function testGetGroupedLineItemsWhenLineItemIsGroupedByField(): void
    {
        $lineItem1 = $this->getLineItem(1);
        $lineItem2 = $this->getLineItem(2);
        $lineItem3 = $this->getLineItem(3);

        self::assertEquals(
            [
                'id:1' => [$lineItem1],
                'id:2' => [$lineItem2],
                'id:3' => [$lineItem3]
            ],
            $this->groupLineItemHelper->getGroupedLineItems(
                new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]),
                'id'
            )
        );
    }

    public function testIsLineItemsGroupedByOrganization(): void
    {
        self::assertFalse($this->groupLineItemHelper->isLineItemsGroupedByOrganization('product.category'));
    }

    public function testGetGroupingFieldPath(): void
    {
        $groupingFieldPath = 'product.category';

        $this->configProvider->expects(self::once())
            ->method('getGroupLineItemsByField')
            ->willReturn($groupingFieldPath);

        self::assertEquals($groupingFieldPath, $this->groupLineItemHelper->getGroupingFieldPath());
    }

    public function testGetGroupingFieldValueWhenLineItemIsGroupedByAssociation(): void
    {
        $organization = $this->getOrganization(10);
        $lineItem = $this->getLineItem(1);
        $this->setLineItemProduct($lineItem, $organization);

        self::assertSame(
            $organization,
            $this->groupLineItemHelper->getGroupingFieldValue($lineItem, 'product.organization')
        );
    }

    public function testGetGroupingFieldValueWhenLineItemIsGroupedByAssociationAndItsValueIsNull(): void
    {
        $lineItem = $this->getLineItem(1);
        $this->setLineItemProduct($lineItem, null);

        self::assertNull(
            $this->groupLineItemHelper->getGroupingFieldValue($lineItem, 'product.organization')
        );
    }

    public function testGetGroupingFieldValueWhenLineItemIsGroupedByAssociationAndFailToGetItsValue(): void
    {
        $lineItem = $this->getLineItem(1);

        self::assertNull(
            $this->groupLineItemHelper->getGroupingFieldValue($lineItem, 'product.organization')
        );
    }

    public function testGetGroupingFieldValueWhenLineItemIsGroupedByAssociationAndCannotGetItsValue(): void
    {
        $lineItem = $this->getLineItem(1);

        self::assertNull(
            $this->groupLineItemHelper->getGroupingFieldValue($lineItem, 'product.undefined')
        );
    }

    public function testGetGroupingFieldValueWhenLineItemIsGroupedByField(): void
    {
        $lineItemId = 1;
        $lineItem = $this->getLineItem($lineItemId);

        self::assertSame(
            $lineItemId,
            $this->groupLineItemHelper->getGroupingFieldValue($lineItem, 'id')
        );
    }
}
