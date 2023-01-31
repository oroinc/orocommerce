<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Listens to Order save event and set identifier for Order if identifier is empty
 */
class OrderListener
{
    /**
     * @var EntityAwareGeneratorInterface
     */
    protected $idGenerator;

    public function __construct(EntityAwareGeneratorInterface $idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }

    public function postPersist(Order $entity, LifecycleEventArgs $args)
    {
        if (!$entity->getIdentifier()) {
            $changeSet = [
                'identifier' => [null, $this->idGenerator->generate($entity)],
            ];

            $args->getObjectManager()->getUnitOfWork()->scheduleExtraUpdate($entity, $changeSet);
        }
    }
}
