<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderListener
{
    /**
     * @var EntityAwareGeneratorInterface
     */
    protected $idGenerator;

    /**
     * @param EntityAwareGeneratorInterface $idGenerator
     */
    public function __construct(EntityAwareGeneratorInterface $idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }

    /**
     * @param Order $entity
     * @param LifecycleEventArgs $args
     */
    public function postPersist(Order $entity, LifecycleEventArgs $args)
    {
        if (!$entity->getIdentifier()) {
            $changeSet = [
                'identifier' => [null, $this->idGenerator->generate($entity)],
            ];

            $args->getEntityManager()->getUnitOfWork()->scheduleExtraUpdate($entity, $changeSet);
        }
    }
}
