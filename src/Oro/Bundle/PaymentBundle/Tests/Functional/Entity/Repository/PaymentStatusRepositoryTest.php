<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

final class PaymentStatusRepositoryTest extends WebTestCase
{
    private PaymentStatusRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repository = self::getContainer()->get('doctrine')->getRepository(PaymentStatus::class);

        $this->loadFixtures([LoadOrders::class]);
    }

    public function testUpsertPaymentStatusInsert(): void
    {
        $entityClass = Order::class;
        $entityIdentifier = 12345;
        $paymentStatusValue = 'paid';
        $force = true;

        $paymentStatus = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier,
            $paymentStatusValue,
            $force
        );

        self::assertNotNull($paymentStatus->getId());
        self::assertEquals($entityClass, $paymentStatus->getEntityClass());
        self::assertEquals($entityIdentifier, $paymentStatus->getEntityIdentifier());
        self::assertEquals($paymentStatusValue, $paymentStatus->getPaymentStatus());
        self::assertTrue($paymentStatus->isForced());
        self::assertNotNull($paymentStatus->getUpdatedAt());
    }

    public function testUpsertPaymentStatusUpdate(): void
    {
        $entityClass = Order::class;
        $entityIdentifier = 12346;
        $initialStatus = 'pending';
        $updatedStatus = 'paid';

        // First insert
        $initialPaymentStatus = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier,
            $initialStatus,
            false
        );

        $initialId = $initialPaymentStatus->getId();
        $initialUpdatedAt = $initialPaymentStatus->getUpdatedAt();

        // Wait a moment to ensure updated_at changes
        sleep(1);

        // Update the same record
        $updatedPaymentStatus = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier,
            $updatedStatus,
            true
        );

        self::assertEquals($initialId, $updatedPaymentStatus->getId());
        self::assertEquals($entityClass, $updatedPaymentStatus->getEntityClass());
        self::assertEquals($entityIdentifier, $updatedPaymentStatus->getEntityIdentifier());
        self::assertEquals($updatedStatus, $updatedPaymentStatus->getPaymentStatus());
        self::assertTrue($updatedPaymentStatus->isForced());
        self::assertGreaterThan($initialUpdatedAt, $updatedPaymentStatus->getUpdatedAt());
    }

    public function testUpsertPaymentStatusWithDefaultForce(): void
    {
        $entityClass = Order::class;
        $entityIdentifier = 12347;
        $paymentStatusValue = 'declined';

        $paymentStatus = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier,
            $paymentStatusValue
        );

        self::assertFalse($paymentStatus->isForced());
    }

    public function testUpsertPaymentStatusMultipleEntities(): void
    {
        $entityClass = Order::class;
        $entityIdentifier1 = 12348;
        $entityIdentifier2 = 12349;
        $paymentStatusValue = 'partially_paid';

        $paymentStatus1 = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier1,
            $paymentStatusValue
        );

        $paymentStatus2 = $this->repository->upsertPaymentStatus(
            $entityClass,
            $entityIdentifier2,
            $paymentStatusValue
        );

        self::assertNotEquals($paymentStatus1->getId(), $paymentStatus2->getId());
        self::assertEquals($paymentStatus1->getEntityClass(), $paymentStatus2->getEntityClass());
        self::assertNotEquals($paymentStatus1->getEntityIdentifier(), $paymentStatus2->getEntityIdentifier());
        self::assertEquals($paymentStatus1->getPaymentStatus(), $paymentStatus2->getPaymentStatus());
    }

    public function testFindAvailablePaymentStatusesForEntityClassWithExistingStatuses(): void
    {
        $entityClass = Order::class;

        $order1 = $this->getReference(LoadOrders::ORDER_1);
        $order2 = $this->getReference(LoadOrders::ORDER_2);
        $order3 = $this->getReference(LoadOrders::ORDER_3);
        $order4 = $this->getReference(LoadOrders::ORDER_4);

        $this->repository->upsertPaymentStatus($entityClass, $order1->getId(), PaymentStatuses::PAID_IN_FULL);
        $this->repository->upsertPaymentStatus($entityClass, $order2->getId(), PaymentStatuses::PENDING);
        $this->repository->upsertPaymentStatus($entityClass, $order3->getId(), PaymentStatuses::PAID_IN_FULL);
        $this->repository->upsertPaymentStatus($entityClass, $order4->getId(), 'custom');

        $availableStatuses = $this->repository->findAvailablePaymentStatusesForEntityClass($entityClass);

        self::assertIsArray($availableStatuses);
        self::assertContains(PaymentStatuses::PAID_IN_FULL, $availableStatuses);
        self::assertContains(PaymentStatuses::PENDING, $availableStatuses);
        self::assertContains('custom', $availableStatuses);

        // Should not contain duplicates
        $uniqueStatuses = array_unique($availableStatuses);
        self::assertCount(count($uniqueStatuses), $availableStatuses);
    }

    public function testFindAvailablePaymentStatusesForEntityClassWithNoStatuses(): void
    {
        $entityClass = User::class;

        $availableStatuses = $this->repository->findAvailablePaymentStatusesForEntityClass($entityClass);

        self::assertIsArray($availableStatuses);
        self::assertEmpty($availableStatuses);
    }
}
