<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

class UpdateDefaultOrderStatuses extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadOrderInternalStatuses::class];
    }

    /**
     * @var EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $qb = $manager->getConnection()->createQueryBuilder();
        $qb->update('oro_order')
            ->set('internal_status_id', ':status')
            ->setParameter('status', OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN)
            ->where($qb->expr()->isNull('internal_status_id'))
            ->execute();
    }
}
