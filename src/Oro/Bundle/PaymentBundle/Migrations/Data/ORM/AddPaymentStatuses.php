<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class AddPaymentStatuses extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var PaymentStatusProvider */
    protected $paymentStatusProvider;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass(PaymentTransaction::class);

        $classNames = $em
            ->createQueryBuilder()
            ->select('pt.entityClass')
            ->from(PaymentTransaction::class, 'pt')
            ->groupBy('pt.entityClass')
            ->getQuery()
            ->getScalarResult();

        $classNames = ArrayUtil::arrayColumn($classNames, 'entityClass');

        foreach ($classNames as $className) {
            $this->doUpdate($className);
        }
    }

    /**
     * @param string $className
     */
    protected function doUpdate($className)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass($className);

        $query = $em->createQueryBuilder()
            ->select('DISTINCT o')
            ->from($className, 'o')
            ->innerJoin(
                'OroPaymentBundle:PaymentTransaction',
                'transaction',
                'WITH',
                'o.id = transaction.entityIdentifier AND transaction.entityClass = :className'
            )
            ->leftJoin(
                'OroPaymentBundle:PaymentStatus',
                'status',
                'WITH',
                'o.id = status.entityIdentifier AND status.entityClass = :className'
            )
            ->setParameter('className', $className)
            ->where('status.id IS NULL')
            ->getQuery();

        $iterableResult = new BufferedQueryResultIterator($query);

        $objects = [];
        foreach ($iterableResult as $entity) {
            $paymentStatusEntity = new PaymentStatus();
            $paymentStatusEntity->setEntityClass($className);
            $paymentStatusEntity->setEntityIdentifier($entity->getId());
            $paymentStatusEntity->setPaymentStatus($this->getPaymentStatusProvider()->getPaymentStatus($entity));
            $em->persist($paymentStatusEntity);
            $objects[] = $paymentStatusEntity;
            if (count($objects) === 100) {
                $em->flush($objects);
                $em->clear($className);
                $objects = [];
            }
        }

        if ($objects) {
            $em->flush($objects);
        }

        $em->clear($className);
    }

    /** @return PaymentStatusProvider */
    public function getPaymentStatusProvider()
    {
        if (!$this->paymentStatusProvider) {
            $this->paymentStatusProvider = $this->container->get('orob2b_payment.provider.payment_status');
        }

        return $this->paymentStatusProvider;
    }
}
