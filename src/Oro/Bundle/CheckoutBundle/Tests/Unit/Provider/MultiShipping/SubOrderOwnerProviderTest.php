<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOwnerProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SubOrderOwnerProviderTest extends TestCase
{
    private PropertyAccessorInterface|MockObject $propertyAccessor;
    private OwnershipMetadataProviderInterface|MockObject $metadataProvider;
    private SubOrderOwnerProvider $provider;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->provider = new SubOrderOwnerProvider($this->propertyAccessor, $this->metadataProvider);
    }

    public function testGetOwnerWhenOwnerSourceIsObject()
    {
        $lineItems = new ArrayCollection([new CheckoutLineItem(), new CheckoutLineItem()]);
        $organization = new Organization();
        $user = new User();
        $organization->addUser($user);
        $category = new Category();

        $ownershipMetadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization_id'
        );

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValueMap([
                [$lineItems->first(), 'product.category', $category],
                [$category, 'organization', $organization]
            ]));

        $owner = $this->provider->getOwner($lineItems, 'product.category:1');
        $this->assertEquals($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceIsScalarValue()
    {
        $lineItem = new CheckoutLineItem();
        $product = new Product();
        $lineItem->setProduct($product);

        $lineItems = new ArrayCollection([$lineItem]);

        $businessUnit = new BusinessUnit();
        $user = new User();
        $businessUnit->addUser($user);

        $ownershipMetadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'owner',
            'business_unit_owner_id'
        );

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValueMap([
                [$lineItem, 'product.sku', 'SKU-TEST'],
                [$product, 'owner', $businessUnit]
            ]));

        $owner = $this->provider->getOwner($lineItems, 'product.sku:SKU-TEST');
        $this->assertEquals($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceWithFreeFromProduct()
    {
        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $lineItem->setCheckout($checkout);

        $lineItems = new ArrayCollection([$lineItem]);

        $user = new User();
        $checkout->setOwner($user);

        $ownershipMetadata = new OwnershipMetadata(
            'USER',
            'owner',
            'user_owner_id'
        );

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($checkout, 'owner')
            ->willReturn($user);

        $owner = $this->provider->getOwner($lineItems, 'other-items');
        $this->assertEquals($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceHasUserOwnership()
    {
        $lineItems = new ArrayCollection([new CheckoutLineItem(), new CheckoutLineItem()]);
        $ownerSource = new \StdClass();
        $user = new User();

        $ownershipMetadata = new OwnershipMetadata(
            'USER',
            'owner',
            'user_owner_id'
        );

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValueMap([
                [$lineItems->first(), 'dummyField', $ownerSource],
                [$ownerSource, 'owner', $user]
            ]));

        $owner = $this->provider->getOwner($lineItems, 'dummyField:1');
        $this->assertEquals($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceHasNoneOwnership()
    {
        $lineItems = new ArrayCollection([new CheckoutLineItem(), new CheckoutLineItem()]);
        $ownerSource = new \StdClass();

        $ownershipMetadata = new OwnershipMetadata('NONE');

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($ownerSource);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner');

        $this->provider->getOwner($lineItems, 'dummyField:1');
    }

    public function testGetOwnerWhenOwnerSourceHasEmptyOwner()
    {
        $lineItems = new ArrayCollection([new CheckoutLineItem(), new CheckoutLineItem()]);
        $category = new Category();

        $ownershipMetadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization_id'
        );

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->returnValueMap([
                [$lineItems->first(), 'product.category', $category],
                [$category, 'organization', null]
            ]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner');

        $this->provider->getOwner($lineItems, 'product.category:1');
    }

    /**
     * @param object $ownerSource
     * @dataProvider getOwnerEntitiesData
     */
    public function testGetOwnerWhenOwnerSourceIsOwnerEntity(object $ownerSource)
    {
        $lineItems = new ArrayCollection([new CheckoutLineItem(), new CheckoutLineItem()]);

        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with()
            ->willReturn($ownerSource);

        $owner = $this->provider->getOwner($lineItems, 'product.testField:1');
        $this->assertInstanceOf(User::class, $owner);
    }

    public function testGetOwnerNoLineItems()
    {
        $lineItems = new ArrayCollection([]);

        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner');

        $this->provider->getOwner($lineItems, 'product.testField:1');
    }

    public function getOwnerEntitiesData()
    {
        $user = new User();

        $organization = new Organization();
        $organization->addUser($user);

        $businessUnit = new BusinessUnit();
        $businessUnit->addUser($user);

        return [
            ['ownerSource' => $user],
            ['ownerSource' => $organization],
            ['ownerSource' => $businessUnit]
        ];
    }
}
