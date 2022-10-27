<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\OrderTax\Specification;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class OrderRequiredTaxRecalculationSpecificationTest extends WebTestCase
{
    /**
     * @var OrderRequiredTaxRecalculationSpecification
     */
    protected $specification;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadOrderItems::class]);

        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();

        $this->specification = new OrderRequiredTaxRecalculationSpecification($uow);
    }

    public function testNotOrderWillNotRequireTaxRecalculation(): void
    {
        self::assertFalse($this->specification->isSatisfiedBy(new \stdClass()));
    }

    public function testOrderWithoutChangesWillNotRequireTaxRecalculation()
    {
        $order = $this->getReference(LoadOrders::ORDER_1);

        self::assertFalse($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedCustomerWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setCustomer($this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1));

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedCustomerUserWillNotRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setCustomerUser($this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL));

        self::assertFalse($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedLineItemsCollectionWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $firstLineItem = $order->getLineItems()[0];
        $order->removeLineItem($firstLineItem);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedBillingAddressZipWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getBillingAddress()->setPostalCode('test');

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedBillingAddressStateWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getBillingAddress()->setRegion(null);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedBillingAddressCountryWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getBillingAddress()->setCountry(null);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedShippingAddressZipWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getShippingAddress()->setPostalCode('test');

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedShippingAddressStateWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getShippingAddress()->setRegion(null);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedShippingAddressCountryWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->getShippingAddress()->setCountry(null);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedLineItemWillNotRequireTaxRecalculationIfNoChangesRelatedToTaxMade()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $firstLineItem = $order->getLineItems()[0];
        $firstLineItem->setShipBy(new \DateTime());
        $firstLineItem->setComment('test');

        self::assertFalse($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedOverriddenShippingCostAmountWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setOverriddenShippingCostAmount(9.9);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedEstimatedShippingCostAmountWillRequireTaxRecalculation()
    {
        /**
         * @var Order $order
         */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setEstimatedShippingCostAmount(376.6);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }
}
