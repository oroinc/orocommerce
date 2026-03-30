<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\DraftSession;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftDeleteControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadOrderLineItemDraftData::class]);

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->draftSessionOrmFilterManager->enable();
    }

    public function testDeleteExistingDraftLineItemMarksAsDeleted(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertTrue($data['successful']);
        self::assertArrayHasKey('widget', $data);
        self::assertArrayHasKey('trigger', $data['widget']);
        self::assertEquals('mediator', $data['widget']['trigger'][0]['eventBroker']);
        self::assertEquals(
            'datagrid:doRefresh:orderDraftGrid:order-line-items-edit-grid',
            $data['widget']['trigger'][0]['name']
        );

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->clear();

        // ORM filter should be disabled again after the entity manager is cleared.
        $this->draftSessionOrmFilterManager->disable();

        $updatedDraftLineItem = $entityManager->getRepository(OrderLineItem::class)->find($draftLineItem->getId());
        self::assertNotNull($updatedDraftLineItem);
        self::assertTrue($updatedDraftLineItem->isDraftDelete());
    }

    public function testDeleteCreatesAndMarksDraftAsDeletedWhenNoDraftExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_2);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);

        $lineItemCountBefore = $entityManager->getRepository(OrderLineItem::class)->count([]);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertTrue($data['successful']);

        $entityManager->clear();

        // ORM filter should be disabled again after the entity manager is cleared.
        $this->draftSessionOrmFilterManager->disable();

        $lineItemCountAfter = $entityManager->getRepository(OrderLineItem::class)->count([]);
        self::assertEquals($lineItemCountBefore + 1, $lineItemCountAfter);

        $draftLineItems = $entityManager->getRepository(OrderLineItem::class)->findBy([
            'draftSource' => $lineItem->getId(),
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertCount(1, $draftLineItems);
        self::assertTrue($draftLineItems[0]->isDraftDelete());
        self::assertEquals($lineItem->getId(), $draftLineItems[0]->getDraftSource()->getId());
    }

    public function testDeleteNewDraftLineItemPhysicallyRemovesIt(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_2);
        /** @var OrderLineItem $newDraftLineItem */
        $newDraftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_3);

        $draftSessionUuid = $newDraftLineItem->getDraftSessionUuid();
        $lineItemId = $newDraftLineItem->getId();

        self::assertNull($newDraftLineItem->getDraftSource());

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItemId,
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertTrue($data['successful']);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->clear();

        $deletedLineItem = $entityManager->getRepository(OrderLineItem::class)->find($lineItemId);
        self::assertNull($deletedLineItem, 'New draft line item should be physically deleted');
    }

    public function testDeleteReturns404WhenOrderDraftNotExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_2);
        $draftSessionUuid = UUIDGenerator::v4();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testDeleteReturns404ForNonExistentLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $nonExistentId = 0;
        $draftSessionUuid = UUIDGenerator::v4();

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $nonExistentId,
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testDeleteRequiresDeletePermission(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        $draftSessionUuid = UUIDGenerator::v4();

        // Re-initialize client without admin authentication
        $this->initClient();

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_order_line_item_draft_delete',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            )
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 401);
    }
}
