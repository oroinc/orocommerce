<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\DraftSession;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Operation\CreateOrderDraftFromRfq;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressesForDraftData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
final class CreateOrderDraftFromRfqTest extends WebTestCase
{
    private CreateOrderDraftFromRfq $createOrderDraftFromRfq;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadRequestData::class,
            LoadCustomerUserAddressesForDraftData::class,
        ]);

        $this->createOrderDraftFromRfq = self::getContainer()
            ->get('oro_rfp.operation.create_order_draft_from_rfq');
        $this->updateUserSecurityToken(self::AUTH_USER);
    }

    public function testCreateOrderDraftFromRfqReturnsOrderWithDraftSessionUuid(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        self::assertInstanceOf(Order::class, $orderDraft);
        self::assertNotEmpty($orderDraft->getDraftSessionUuid());
    }

    public function testCreateOrderDraftFromRfqSynchronizesBasicFieldsFromRequest(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        self::assertSame($request->getCustomer()->getId(), $orderDraft->getCustomer()->getId());
        self::assertSame($request->getCustomerUser()->getId(), $orderDraft->getCustomerUser()->getId());
        self::assertSame($request->getWebsite()->getId(), $orderDraft->getWebsite()->getId());
        self::assertSame($request->getPoNumber(), $orderDraft->getPoNumber());
        self::assertNotEmpty($orderDraft->getCurrency());
        self::assertSame(Request::class, $orderDraft->getSourceEntityClass());
        self::assertSame($request->getId(), $orderDraft->getSourceEntityId());
    }

    public function testCreateOrderDraftFromRfqCreatesLineItemForEachRequestProduct(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $expectedLineItemCount = $request->getRequestProducts()->count();

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        self::assertSame($expectedLineItemCount, $orderDraft->getLineItems()->count());
    }

    public function testAdditionalFieldsAreSynchronizedFromRequest(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        self::assertNotNull($orderDraft->getOrganization());
        self::assertSame($request->getOrganization()->getId(), $orderDraft->getOrganization()->getId());

        self::assertNotNull($orderDraft->getShipUntil());

        self::assertSame($request->getNote(), $orderDraft->getCustomerNotes());

        self::assertNotEmpty($orderDraft->getSourceEntityIdentifier());
        self::assertSame($request->getIdentifier(), $orderDraft->getSourceEntityIdentifier());
    }

    public function testBillingAndShippingAddressesAreSetBySetOrderAddressListener(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        self::assertNotNull($orderDraft->getBillingAddress());
        self::assertInstanceOf(OrderAddress::class, $orderDraft->getBillingAddress());

        self::assertNotNull($orderDraft->getShippingAddress());
        self::assertInstanceOf(OrderAddress::class, $orderDraft->getShippingAddress());
    }

    public function testAllLineItemsHaveSameDraftSessionUuidAsOrderDraft(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        $draftSessionUuid = $orderDraft->getDraftSessionUuid();

        foreach ($orderDraft->getLineItems() as $lineItem) {
            self::assertSame($draftSessionUuid, $lineItem->getDraftSessionUuid());
        }
    }

    public function testLineItemsHaveProductDataSynchronizedFromRequestProducts(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        $lineItems = array_values($orderDraft->getLineItems()->toArray());
        $requestProducts = array_values($request->getRequestProducts()->toArray());

        self::assertCount(count($requestProducts), $lineItems);

        foreach ($requestProducts as $index => $requestProduct) {
            $lineItem = $lineItems[$index];

            self::assertSame($requestProduct->getProduct()->getId(), $lineItem->getProduct()->getId());
            self::assertSame($requestProduct->getProductSku(), $lineItem->getProductSku());
            self::assertSame(1, (int) $lineItem->getQuantity());
            self::assertNotNull($lineItem->getProductUnit());
        }
    }

    public function testLineItemsHaveRequestProductLinkedToSourceRequestProduct(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        $lineItems = array_values($orderDraft->getLineItems()->toArray());
        $requestProducts = array_values($request->getRequestProducts()->toArray());

        foreach ($requestProducts as $index => $requestProduct) {
            $lineItem = $lineItems[$index];

            self::assertSame($requestProduct->getId(), $lineItem->getRequestProduct()->getId());
        }
    }

    public function testLineItemChecksumIsGeneratedForEachLineItemByListener(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        foreach ($orderDraft->getLineItems() as $lineItem) {
            self::assertNotNull($lineItem->getChecksum());
            self::assertNotSame('', $lineItem->getChecksum());
        }
    }

    public function testLineItemPricesHaveCorrectCurrencyWhenSet(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        $orderDraft = $this->createOrderDraftFromRfq->createOrderDraftFromRfq($request);

        $orderCurrency = $orderDraft->getCurrency();

        foreach ($orderDraft->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();
            if ($price === null) {
                continue;
            }

            self::assertInstanceOf(Price::class, $price);
            self::assertSame($orderCurrency, $price->getCurrency());
        }
    }
}
