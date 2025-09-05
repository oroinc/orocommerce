<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Repository for managing payment statuses.
 */
class PaymentStatusRepository extends ServiceEntityRepository
{
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $registry, string $entityClass, AclHelper $aclHelper)
    {
        parent::__construct($registry, $entityClass);

        $this->aclHelper = $aclHelper;
    }

    /**
     * Upserts a payment status for a given entity.
     * If the payment status already exists, it updates the existing record.
     * If it does not exist, it creates a new record.
     *
     * @param string $entityClass The class name of the entity for which the payment status is being set.
     * @param int $entityIdentifier The identifier of the entity for which the payment status is being set.
     * @param string $paymentStatus The payment status to set.
     * @param bool $force If true, the payment status will be set forcefully,
     *  so it will not be recalculated in the future anymore.
     *
     * @return PaymentStatus
     */
    public function upsertPaymentStatus(
        string $entityClass,
        int $entityIdentifier,
        string $paymentStatus,
        bool $force = false
    ): PaymentStatus {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $sql = 'INSERT INTO oro_payment_status (entity_class, entity_identifier, payment_status, forced, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?) '
            . 'ON CONFLICT (entity_class, entity_identifier) '
            . 'DO UPDATE SET '
            . 'payment_status = EXCLUDED.payment_status, '
            . 'forced = EXCLUDED.forced, '
            . 'updated_at = EXCLUDED.updated_at '
            . 'RETURNING id';

        $result = $connection->executeQuery(
            $sql,
            [$entityClass, $entityIdentifier, $paymentStatus, $force, $now],
            [Types::STRING, Types::STRING, Types::STRING, Types::BOOLEAN, Types::DATETIME_MUTABLE]
        );

        $row = $result->fetchAssociative();

        $paymentStatusEntity = $this->find($row['id']);

        $entityManager->refresh($paymentStatusEntity);

        return $paymentStatusEntity;
    }

    public function findAvailablePaymentStatusesForEntityClass(string $entityClass): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('paymentStatus.paymentStatus')
            ->from($entityClass, 'entity')
            ->innerJoin(
                PaymentStatus::class,
                'paymentStatus',
                Join::WITH,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('paymentStatus.entityClass', ':entityClass'),
                    $queryBuilder->expr()->eq('paymentStatus.entityIdentifier', 'entity.id')
                )
            )
            ->setParameter('entityClass', $entityClass, Types::STRING)
            ->groupBy('paymentStatus.paymentStatus');

        return $this->aclHelper->apply($queryBuilder)->getSingleColumnResult();
    }
}
