<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\ProductLineItemsHolderFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemsHolderStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\TestCase;

class ProductLineItemsHolderFactoryTest extends TestCase
{
    private ProductLineItemsHolderFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ProductLineItemsHolderFactory();
    }

    public function testCreateFromLineItemsWhenNoLineItemsAndIsArray(): void
    {
        self::assertEquals(
            new ProductLineItemsHolderDTO(),
            $this->factory->createFromLineItems([])
        );
    }

    public function testCreateFromLineItemsWhenNoLineItems(): void
    {
        self::assertEquals(
            new ProductLineItemsHolderDTO(),
            $this->factory->createFromLineItems(new ArrayCollection())
        );
    }

    public function testCreateFromLineItemsWhenHasLineItems(): void
    {
        $lineItem1 = new ProductLineItem(10);
        $lineItem2 = new ProductLineItem(20);

        self::assertEquals(
            (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItems(): void
    {
        $lineItemsHolderStub = new ProductLineItemsHolderStub();
        $lineItem1 = (new ProductLineItem(10))
            ->setLineItemsHolder($lineItemsHolderStub);
        $lineItem2 = new ProductLineItem(20);

        self::assertEquals(
            (new ProductLineItemsHolderDTO())
                ->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItemsWithWebsite(): void
    {
        $lineItemsHolderStub = (new ProductLineItemsHolderStub())
            ->setWebsite(new Website());
        $lineItem1 = (new ProductLineItem(10))
            ->setLineItemsHolder($lineItemsHolderStub);
        $lineItem2 = new ProductLineItem(20);

        self::assertEquals(
            (new ProductLineItemsHolderDTO())
                ->setLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
                ->setWebsite($lineItemsHolderStub->getWebsite()),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItemsWithCustomerUser(): void
    {
        $lineItemsHolderStub = (new ProductLineItemsHolderStub())
            ->setWebsite(new Website())
            ->setCustomer(new Customer())
            ->setCustomerUser(new CustomerUser());

        $lineItem1 = (new ProductLineItem(10))
            ->setLineItemsHolder($lineItemsHolderStub);
        $lineItem2 = (new ProductLineItem(20))
            ->setLineItemsHolder($lineItemsHolderStub);

        self::assertEquals(
            (new ProductLineItemsHolderDTO())
                ->setLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
                ->setWebsite($lineItemsHolderStub->getWebsite())
                ->setCustomer($lineItemsHolderStub->getCustomer())
                ->setCustomerUser($lineItemsHolderStub->getCustomerUser()),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }
}
