<?php

namespace OroB2B\Bundle\OrderBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use OroB2B\Bundle\OrderBundle\Entity\Order;

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
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        /** @var Order $entity */
        $entity = $args->getEntity();

        if ($entity instanceof Order && !$entity->getIdentifier()) {
            $entity->setIdentifier($this->idGenerator->generate($entity));
        }
    }
}
