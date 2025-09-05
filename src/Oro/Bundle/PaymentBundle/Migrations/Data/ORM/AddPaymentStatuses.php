<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Selects PaymentTransaction.entityClass,
 * then use Repository of selected class
 * then updates Oro\Bundle\PaymentBundle\Entity\PaymentStatus table with selected data
 */
class AddPaymentStatuses extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $classNames = $this->createQueryBuilder($manager)
            ->select('pt.entityClass')
            ->from(PaymentTransaction::class, 'pt')
            ->groupBy('pt.entityClass')
            ->getQuery()
            ->getScalarResult();
        $classNames = \array_column($classNames, 'entityClass');
        foreach ($classNames as $className) {
            $this->doUpdate($manager, $className);
        }
    }

    private function doUpdate(ObjectManager $manager, string $className): void
    {
        $query = $this->createQueryBuilder($manager)
            ->select('DISTINCT o')
            ->from($className, 'o')
            ->innerJoin(
                PaymentTransaction::class,
                'transaction',
                'WITH',
                'o.id = transaction.entityIdentifier AND transaction.entityClass = :className'
            )
            ->leftJoin(
                PaymentStatus::class,
                'status',
                'WITH',
                'o.id = status.entityIdentifier AND status.entityClass = :className'
            )
            ->setParameter('className', $className)
            ->where('status.id IS NULL')
            ->getQuery();

        $iterableResult = new BufferedIdentityQueryResultIterator($query);

        /** @var PaymentStatusCalculatorInterface $paymentStatusCalculator */
        $paymentStatusCalculator = $this->container->get('oro_payment.payment_status.calculator');

        $objects = [];
        foreach ($iterableResult as $entity) {
            $paymentStatusEntity = new PaymentStatus();
            $paymentStatusEntity->setEntityClass($className);
            $paymentStatusEntity->setEntityIdentifier($entity->getId());
            $paymentStatusEntity->setPaymentStatus($paymentStatusCalculator->calculatePaymentStatus($entity));

            $manager->persist($paymentStatusEntity);
            $objects[] = $paymentStatusEntity;
            if (\count($objects) === 100) {
                $manager->flush($objects);
                $manager->clear($className);
                $objects = [];
            }
        }

        if ($objects) {
            $manager->flush($objects);
        }

        $manager->clear($className);
    }

    private function createQueryBuilder(ObjectManager $manager): QueryBuilder
    {
        return $manager->createQueryBuilder();
    }
}
