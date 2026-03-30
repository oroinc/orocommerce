<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\LoadOrderDraftOnRequestListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @dbIsolationPerTest
 */
final class OrderDraftSyncExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private EntityManagerInterface $entityManager;
    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOrders::class]);

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class);

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->setDraftSessionUuid(null);
        $this->clearOrderDraftFromRequest();
        $this->resetExtension();
        $this->draftSessionOrmFilterManager->enable();
    }

    public function testSynchronizesOrderFromExistingDraft(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderDraft = $this->createOrderDraft($order, $draftSessionUuid);
        $orderDraft->setPoNumber('SYNCED_FROM_DRAFT_PO');
        $orderDraft->setCustomerNotes('Synced notes from draft');

        $this->entityManager->persist($orderDraft);
        $this->entityManager->flush();

        $this->setOrderDraftOnRequest($orderDraft);

        self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        self::assertEquals(
            'SYNCED_FROM_DRAFT_PO',
            $order->getPoNumber(),
            'Order PO number should be synchronized from draft'
        );
        self::assertEquals(
            'Synced notes from draft',
            $order->getCustomerNotes(),
            'Order customer notes should be synchronized from draft'
        );
    }

    public function testCreatesOrderDraftWhenItDoesNotExist(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->clearOrderDraftFromRequest();

        $orderDraftCountBefore = $this->entityManager->getRepository(Order::class)->count([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        $this->entityManager->clear();

        $orderDraftCountAfter = $this->entityManager->getRepository(Order::class)->count([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertEquals(
            $orderDraftCountBefore + 1,
            $orderDraftCountAfter,
            'Order draft should be created when it does not exist'
        );

        $createdDraft = $this->entityManager->getRepository(Order::class)->findOneBy([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertNotNull($createdDraft, 'Order draft should exist after form creation');
        self::assertEquals($order->getId(), $createdDraft->getDraftSource()->getId());
    }

    public function testDoesNotCreateDuplicateDraftOnMultipleFormSetData(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->clearOrderDraftFromRequest();

        $form = self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        $form->setData($order);

        $this->entityManager->clear();

        $orderDrafts = $this->entityManager->getRepository(Order::class)->findBy([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertCount(
            1,
            $orderDrafts,
            'Only one draft should be created even when form data is set multiple times'
        );
    }

    public function testExtensionUsesCachedDraftOnMultipleFormSetData(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderDraft = $this->createOrderDraft($order, $draftSessionUuid);
        $orderDraft->setPoNumber('EXISTING_DRAFT_PO');

        $this->entityManager->persist($orderDraft);
        $this->entityManager->flush();

        $this->setOrderDraftOnRequest($orderDraft);

        $form = self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        self::assertEquals(
            'EXISTING_DRAFT_PO',
            $order->getPoNumber(),
            'Order should be synchronized from existing draft on first setData'
        );

        $this->clearOrderDraftFromRequest();

        $anotherDraft = $this->createOrderDraft($order, $draftSessionUuid);
        $anotherDraft->setPoNumber('DIFFERENT_DRAFT_PO');
        $this->entityManager->persist($anotherDraft);
        $this->entityManager->flush();

        $order->setPoNumber('MODIFIED_ORDER_PO');

        $form->setData($order);

        self::assertEquals(
            'EXISTING_DRAFT_PO',
            $order->getPoNumber(),
            'Order should be synchronized from cached draft on subsequent setData calls'
        );
    }

    public function testOrderDraftCreatedForNewOrderWithoutId(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $existingOrder */
        $existingOrder = $this->getReference(LoadOrders::ORDER_1);

        $newOrder = new Order();
        $newOrder->setCustomer($existingOrder->getCustomer());
        $newOrder->setOrganization($existingOrder->getOrganization());
        $newOrder->setOwner($existingOrder->getOwner());
        $newOrder->setCurrency('USD');
        $newOrder->setWebsite($existingOrder->getWebsite());
        $newOrder->setPoNumber('NEW_ORDER_PO');

        $this->clearOrderDraftFromRequest();

        $orderDraftCountBefore = $this->entityManager->getRepository(Order::class)->count([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::createForm(OrderType::class, $newOrder, [
            'draft_session_sync' => true,
        ]);

        $this->entityManager->clear();

        $orderDraftCountAfter = $this->entityManager->getRepository(Order::class)->count([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertEquals(
            $orderDraftCountBefore + 1,
            $orderDraftCountAfter,
            'Order draft should be created for a new order'
        );

        $createdDraft = $this->entityManager->getRepository(Order::class)->findOneBy([
            'draftSessionUuid' => $draftSessionUuid,
        ]);

        self::assertNotNull($createdDraft, 'Order draft should exist');
        self::assertNull(
            $createdDraft->getDraftSource(),
            'Draft source should be null for new orders without ID'
        );
    }

    public function testDraftSynchronizationUpdatesMultipleOrderFields(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderDraft = $this->createOrderDraft($order, $draftSessionUuid);
        $orderDraft->setPoNumber('MULTI_FIELD_PO');
        $orderDraft->setCustomerNotes('Multi field notes');
        $orderDraft->setShipUntil(new \DateTime('2030-01-15'));

        $this->entityManager->persist($orderDraft);
        $this->entityManager->flush();

        $this->setOrderDraftOnRequest($orderDraft);

        self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        self::assertEquals('MULTI_FIELD_PO', $order->getPoNumber());
        self::assertEquals('Multi field notes', $order->getCustomerNotes());
        self::assertEquals('2030-01-15', $order->getShipUntil()->format('Y-m-d'));
    }

    public function testFormSubmissionWithDraftSyncEnabled(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderDraft = $this->createOrderDraft($order, $draftSessionUuid);
        $orderDraft->setPoNumber('PRE_SUBMIT_PO');

        $this->entityManager->persist($orderDraft);
        $this->entityManager->flush();

        $this->setOrderDraftOnRequest($orderDraft);

        $form = self::createForm(OrderType::class, $order, [
            'draft_session_sync' => true,
        ]);

        self::assertEquals('PRE_SUBMIT_PO', $order->getPoNumber());

        $submitData = [
            'customer' => $order->getCustomer()->getId(),
            'currency' => 'USD',
            'poNumber' => 'SUBMITTED_PO',
        ];

        if ($form->has('website')) {
            $submitData['website'] = $order->getWebsite()->getId();
        }

        $form->submit($submitData);

        self::assertEquals('SUBMITTED_PO', $order->getPoNumber());
    }

    private function setDraftSessionUuid(?string $draftSessionUuid): void
    {
        /** @var RequestContextAwareInterface $router */
        $router = self::getContainer()->get('router');
        $context = $router->getContext();
        $context->setParameter('orderDraftSessionUuid', $draftSessionUuid);
    }

    private function setOrderDraftOnRequest(?Order $orderDraft): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');

        $request = $requestStack->getMainRequest();
        if ($request === null) {
            $request = new Request();
            $requestStack->push($request);
        }

        $request->attributes->set(
            LoadOrderDraftOnRequestListener::ORDER_DRAFT,
            $orderDraft
        );
    }

    private function clearOrderDraftFromRequest(): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');

        $requestStack->getMainRequest()?->attributes->remove(LoadOrderDraftOnRequestListener::ORDER_DRAFT);
    }

    private function createOrderDraft(Order $order, string $draftSessionUuid): Order
    {
        return self::getContainer()
            ->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);
    }

    private function resetExtension(): void
    {
        $extension = self::getContainer()->get('oro_order.form.extension.order_type_draft_edit_mode');
        if ($extension instanceof ResetInterface) {
            $extension->reset();
        }
    }
}
