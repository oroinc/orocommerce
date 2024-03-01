<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\OrderTax\Specification;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderLineItemRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class OrderLineItemRequiredTaxRecalculationSpecificationTest extends WebTestCase
{
    private OrderLineItemRequiredTaxRecalculationSpecification $specification;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadOrderItems::class]);

        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();

        $this->specification = new OrderLineItemRequiredTaxRecalculationSpecification($uow);
    }

    public function testNotOrderLineItemWillNotRequireTaxRecalculation(): void
    {
        self::assertFalse($this->specification->isSatisfiedBy(new \stdClass()));
    }

    public function testOrderWithChangedLineItemQuantityWillRequireTaxRecalculation(): void
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $orderLineItem->setQuantity(123456);

        self::assertTrue($this->specification->isSatisfiedBy($orderLineItem));
    }

    public function testOrderWithChangedLineItemProductWillRequireTaxRecalculation(): void
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $orderLineItem->setProduct($this->getReference(LoadProductData::PRODUCT_2));

        self::assertTrue($this->specification->isSatisfiedBy($orderLineItem));
    }

    public function testOrderWithChangedLineItemProductUnitWillRequireTaxRecalculation(): void
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $orderLineItem->setProductUnit($this->getReference(LoadProductUnits::BOTTLE));

        self::assertTrue($this->specification->isSatisfiedBy($orderLineItem));
    }

    public function testOrderWithChangedLineItemPriceWillRequireTaxRecalculation(): void
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $price = (new Price())
            ->setValue(123456)
            ->setCurrency('USD');
        $orderLineItem->setPrice($price);

        self::assertTrue($this->specification->isSatisfiedBy($orderLineItem));
    }

    public function testOrderWithChangedLineItemWillNotRequireTaxRecalculationIfNoChangesRelatedToTaxMade(): void
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $orderLineItem
            ->setShipBy(new \DateTime())
            ->setComment('test');

        self::assertFalse($this->specification->isSatisfiedBy($orderLineItem));
    }

    public function testOrderWithChangedKitItemsCollectionChanged(): void
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $productKit = $this->getReference(LoadProductData::PRODUCT_3);
        $kitLineItemProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $productKit->setType(Product::TYPE_KIT);

        $productKitItemProduct = new ProductKitItemProduct();
        $productKitItemProduct->setProduct($kitLineItemProduct);

        $kitLineItem = new ProductKitItem();
        $kitLineItem->setDefaultLabel('Base Unit');
        $kitLineItem->setProductKit($productKit);
        $kitLineItem->addKitItemProduct($productKitItemProduct);

        $manager->persist($productKitItemProduct);
        $manager->persist($kitLineItem);
        $manager->flush();

        $orderKitLineItem = new OrderProductKitItemLineItem();
        $orderKitLineItem->setPrice(Price::create(100, 'USD'));
        $orderKitLineItem->setProduct($kitLineItemProduct);
        $orderKitLineItem->setProductUnit($kitLineItemProduct->getPrimaryUnitPrecision()->getUnit());
        $orderKitLineItem->setKitItem($kitLineItem);

        $orderLineItem->addKitItemLineItem($orderKitLineItem);

        $manager->persist($orderKitLineItem);
        $manager->flush();

        $orderKitLineItem->setQuantity(5);

        self::assertTrue($this->specification->isSatisfiedBy($orderLineItem));
    }
}
