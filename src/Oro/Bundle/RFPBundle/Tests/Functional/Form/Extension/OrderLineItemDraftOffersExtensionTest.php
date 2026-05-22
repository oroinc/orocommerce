<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\RFPBundle\Entity\Request as RfqRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\OffersType;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestProductItemData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftOffersExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadOrders::class,
            LoadRequestProductItemData::class,
        ]);
    }

    public function testOffersFieldIsNotAddedWhenNoRequestProduct(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFalse($form->has('offers'), 'Field "offers" is not expected to be present when no offers');
    }

    public function testOffersFieldIsAddedWhenRequestProductHasRequestProductItems(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getReference('request-product-1');

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);
        $lineItem->setRequestProduct($requestProduct);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'offers', OffersType::class, [
            'label' => 'oro.order.orderlineitem.draft_update_form.offers.label',
            'required' => false,
            'mapped' => false,
            'placeholder' => null,
        ]);
    }

    public function testOffersFieldContainsCorrectOffersDataWhenCurrencyMatches(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $requestProduct = $this->createPersistedFreeFormRequestProductWithUsdOffer();

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);
        $lineItem->setCurrency('USD');
        $lineItem->setRequestProduct($requestProduct);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'offers', OffersType::class, [
            'label' => 'oro.order.orderlineitem.draft_update_form.offers.label',
            'required' => false,
            'mapped' => false,
            'placeholder' => null,
            'offers' => [['unit' => 'milliliter', 'quantity' => 3.0, 'price' => 25.0, 'currency' => 'USD']],
        ]);
    }

    public function testOffersFieldFiltersOutOffersWithNonMatchingCurrency(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $requestProduct = $this->createPersistedFreeFormRequestProductWithUsdOffer();

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);
        $lineItem->setCurrency('EUR');
        $lineItem->setRequestProduct($requestProduct);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFalse($form->has('offers'), 'Field "offers" is not expected to be present when no offers');
    }

    /**
     * Persists and returns a freeform {@see RequestProduct} (no product set) with one USD offer:
     * unit=milliliter, quantity=3.0, price=25.0 USD.
     */
    private function createPersistedFreeFormRequestProductWithUsdOffer(): RequestProduct
    {
        /** @var ProductUnit $milliliterUnit */
        $milliliterUnit = $this->getReference(LoadProductUnits::MILLILITER);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(RfqRequest::class);

        $rfqRequest = (new RfqRequest())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail('test@example.com')
            ->setCompany('Test Company');

        $requestProduct = new RequestProduct();
        $requestProduct->setProductSku('freeform-sku');

        $requestProductItem = (new RequestProductItem())
            ->setProductUnit($milliliterUnit)
            ->setQuantity(3.0)
            ->setPrice(Price::create(25.0, 'USD'));

        $requestProduct->addRequestProductItem($requestProductItem);
        $rfqRequest->addRequestProduct($requestProduct);

        $entityManager->persist($rfqRequest);
        $entityManager->flush();

        return $requestProduct;
    }
}
