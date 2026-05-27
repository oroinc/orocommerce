<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Creates ORDER_1 with two line items (LINE_ITEM_1, LINE_ITEM_2),
 * tax rules and addresses for tax calculation,
 * and an order draft that marks LINE_ITEM_2 for deletion.
 */
class LoadOrderLineItemDraftDataWithDeletedItemAndTaxes extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string LINE_ITEM_1 = 'deleted_draft_test.line_item_1';
    public const string LINE_ITEM_2 = 'deleted_draft_test.line_item_2';
    public const string LINE_ITEM_DRAFT_DELETED = 'deleted_draft_test.line_item_draft_deleted';
    public const string ORDER_DRAFT = 'deleted_draft_test.order_draft';
    public const string ORDER_DRAFT_ALL_DELETED = 'deleted_draft_test.order_draft_all_deleted';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrders::class,
            LoadProductUnitPrecisions::class,
            LoadTaxRules::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $order->setCustomer($customer);

        $this->addAddresses($manager, $order);

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var ProductUnit $unitLiter */
        $unitLiter = $this->getReference(LoadProductUnits::LITER);
        /** @var ProductUnit $unitBottle */
        $unitBottle = $this->getReference(LoadProductUnits::BOTTLE);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);
        $lineItem1->setProductSku($product1->getSku());
        $lineItem1->setQuantity(10);
        $lineItem1->setProductUnit($unitLiter);
        $lineItem1->setPrice(Price::create(100, 'USD'));
        $lineItem1->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $order->addLineItem($lineItem1);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);
        $lineItem2->setProductSku($product2->getSku());
        $lineItem2->setQuantity(5);
        $lineItem2->setProductUnit($unitBottle);
        $lineItem2->setPrice(Price::create(50, 'USD'));
        $lineItem2->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $order->addLineItem($lineItem2);

        $manager->persist($lineItem1);
        $manager->persist($lineItem2);
        $manager->flush();

        $this->setReference(self::LINE_ITEM_1, $lineItem1);
        $this->setReference(self::LINE_ITEM_2, $lineItem2);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = $this->container->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);
        $manager->persist($orderDraft);

        $draftDeletedLineItem = new OrderLineItem();
        $draftDeletedLineItem->setProduct($product2);
        $draftDeletedLineItem->setProductSku($product2->getSku());
        $draftDeletedLineItem->setQuantity(5);
        $draftDeletedLineItem->setProductUnit($unitBottle);
        $draftDeletedLineItem->setPrice(Price::create(50, 'USD'));
        $draftDeletedLineItem->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $draftDeletedLineItem->setDraftSessionUuid($draftSessionUuid);
        $draftDeletedLineItem->setDraftSource($lineItem2);
        $draftDeletedLineItem->setDraftDelete(true);

        $orderDraft->addLineItem($draftDeletedLineItem);
        $manager->persist($draftDeletedLineItem);
        $manager->flush();

        $this->setReference(self::LINE_ITEM_DRAFT_DELETED, $draftDeletedLineItem);
        $this->setReference(self::ORDER_DRAFT, $orderDraft);

        // Second draft: marks both line items for deletion.
        $allDeletedDraftUuid = UUIDGenerator::v4();
        $orderDraftAllDeleted = $this->container->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $allDeletedDraftUuid);
        $manager->persist($orderDraftAllDeleted);

        $draftDeletedLineItem1 = new OrderLineItem();
        $draftDeletedLineItem1->setProduct($product1);
        $draftDeletedLineItem1->setProductSku($product1->getSku());
        $draftDeletedLineItem1->setQuantity(10);
        $draftDeletedLineItem1->setProductUnit($unitLiter);
        $draftDeletedLineItem1->setPrice(Price::create(100, 'USD'));
        $draftDeletedLineItem1->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $draftDeletedLineItem1->setDraftSessionUuid($allDeletedDraftUuid);
        $draftDeletedLineItem1->setDraftSource($lineItem1);
        $draftDeletedLineItem1->setDraftDelete(true);
        $orderDraftAllDeleted->addLineItem($draftDeletedLineItem1);
        $manager->persist($draftDeletedLineItem1);

        $draftDeletedLineItem2 = new OrderLineItem();
        $draftDeletedLineItem2->setProduct($product2);
        $draftDeletedLineItem2->setProductSku($product2->getSku());
        $draftDeletedLineItem2->setQuantity(5);
        $draftDeletedLineItem2->setProductUnit($unitBottle);
        $draftDeletedLineItem2->setPrice(Price::create(50, 'USD'));
        $draftDeletedLineItem2->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $draftDeletedLineItem2->setDraftSessionUuid($allDeletedDraftUuid);
        $draftDeletedLineItem2->setDraftSource($lineItem2);
        $draftDeletedLineItem2->setDraftDelete(true);
        $orderDraftAllDeleted->addLineItem($draftDeletedLineItem2);
        $manager->persist($draftDeletedLineItem2);

        $manager->flush();

        $this->setReference(self::ORDER_DRAFT_ALL_DELETED, $orderDraftAllDeleted);
    }

    private function addAddresses(ObjectManager $manager, Order $order): void
    {
        $billingAddress = new OrderAddress();
        $billingAddress->setCountry(
            $manager->getRepository(Country::class)->find(LoadTaxJurisdictions::COUNTRY_US)
        );
        $billingAddress->setRegion(
            $manager->getRepository(Region::class)->find(LoadTaxJurisdictions::STATE_US_NY)
        );
        $billingAddress->setPostalCode('10001');
        $billingAddress->setStreet('123 Main St');
        $billingAddress->setCity('New York');

        $shippingAddress = clone $billingAddress;

        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
    }
}
