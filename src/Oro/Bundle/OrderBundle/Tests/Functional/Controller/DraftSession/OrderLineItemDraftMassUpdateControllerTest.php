<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftMassUpdateControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadOrderLineItemDraftData::class]);

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();

        $this->clearCache();
    }

    private function clearCache(): void
    {
        self::getContainer()->get('oro_tax.taxation_provider.cache')->clear();
        $matchers = self::getContainer()->get('oro_tax.address_matcher_registry')->getMatchers();
        foreach ($matchers as $matcher) {
            if ($matcher instanceof ResetInterface) {
                $matcher->reset();
            }
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->draftSessionOrmFilterManager->enable();
    }

    public function testWithMultipleLineItems(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_2);

        /** @var OrderLineItem $lineItem1 */
        $lineItem1 = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);
        /** @var OrderLineItem $lineItem2 */
        $lineItem2 = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_3);

        // ORDER_LINE_ITEM_3 is an order line item draft.
        $orderDraft = $lineItem2->getOrder();
        $draftSessionUuid = $orderDraft->getDraftSessionUuid();

        $orderLineItemIds = sprintf('%d,%d', $lineItem1->getId(), $lineItem2->getId());

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_order_line_item_draft_mass_update',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemIds' => $orderLineItemIds,
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();

        $data = self::getJsonResponseContent($result, 200);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('lineItems', $data);
        self::assertCount(2, $data['lineItems']);

        // Verify first line item
        self::assertEquals($lineItem1->getId(), $data['lineItems'][0]['lineItemId']);
        self::assertArrayHasKey('html', $data['lineItems'][0]);
        self::assertStringContainsString('oro_order_line_item_draft', $data['lineItems'][0]['html']);

        // Verify second line item
        self::assertEquals($lineItem2->getId(), $data['lineItems'][1]['lineItemId']);
        self::assertArrayHasKey('html', $data['lineItems'][1]);
        self::assertStringContainsString('oro_order_line_item_draft', $data['lineItems'][1]['html']);
    }

    public function testWithSingleLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_order_line_item_draft_mass_update',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemIds' => (string)$lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();

        $data = self::getJsonResponseContent($result, 200);
        self::assertTrue($data['success']);
        self::assertCount(1, $data['lineItems']);
        self::assertEquals($lineItem->getId(), $data['lineItems'][0]['lineItemId']);
    }

    public function testReturns404WithNonExistentLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_order_line_item_draft_mass_update',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemIds' => 0,
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testReturns404WhenOrderDraftNotExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $draftSessionUuid = UUIDGenerator::v4();
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_order_line_item_draft_mass_update',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemIds' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testHasDeleteUrl(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_order_line_item_draft_mass_update',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemIds' => (string)$lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        $expectedDeleteUrl = $this->getUrl(
            'oro_order_line_item_draft_delete',
            [
                'orderId' => $order->getId(),
                'orderLineItemId' => $lineItem->getId(),
                'orderDraftSessionUuid' => $draftSessionUuid,
            ]
        );

        self::assertStringContainsString($expectedDeleteUrl, $data['lineItems'][0]['html']);
    }
}
