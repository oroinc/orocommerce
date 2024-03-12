<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\OrderTax\Specification;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderKitLineItemRequiredTaxRecalculationSpecification;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class OrderKitLineItemRequiredTaxRecalculationSpecificationTest extends WebTestCase
{
    private OrderKitLineItemRequiredTaxRecalculationSpecification $specification;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_product_kit_line_items.yml'
        ]);

        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();

        $this->specification = new OrderKitLineItemRequiredTaxRecalculationSpecification($uow);
    }

    public function testNotOrderKitLineItemWillNotRequireTaxRecalculation(): void
    {
        self::assertFalse($this->specification->isSatisfiedBy(new \stdClass()));
    }

    public function testOrderKitLineItemWithoutChangesWillNotRequireTaxRecalculation(): void
    {
        /** @var OrderProductKitItemLineItem $orderKitLineItem */
        $orderKitLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        self::assertFalse($this->specification->isSatisfiedBy($orderKitLineItem));
    }

    public function testOrderWithChangedKitLineItemPriceWillRequireTaxRecalculation(): void
    {
        /** @var OrderProductKitItemLineItem $orderKitLineItem */
        $orderKitLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');
        $price = (new Price())
            ->setValue(123456)
            ->setCurrency('USD');
        $orderKitLineItem->setPrice($price);

        self::assertTrue($this->specification->isSatisfiedBy($orderKitLineItem));
    }

    public function testOrderWithChangedKitLineItemQuantityWillRequireTaxRecalculation(): void
    {
        /** @var OrderProductKitItemLineItem $orderKitLineItem */
        $orderKitLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');
        $orderKitLineItem->setQuantity(123456);

        self::assertTrue($this->specification->isSatisfiedBy($orderKitLineItem));
    }

    public function testNewOrderKitLineItemWillRequireTaxRecalculation(): void
    {
        /** @var OrderProductKitItemLineItem $orderKitLineItem */
        $orderKitLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');
        ReflectionUtil::setId($orderKitLineItem, null);

        self::assertTrue($this->specification->isSatisfiedBy($orderKitLineItem));
    }
}
