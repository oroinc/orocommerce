<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository($className);

        /** @var AbstractEnumValue $status */
        foreach ($repository->findAll() as $status) {
            $referenceRepository->set('order_internal_status_' . $status->getId(), $status);
        }
    }
}
