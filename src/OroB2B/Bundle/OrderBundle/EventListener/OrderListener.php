<?php

namespace OroB2B\Bundle\OrderBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class OrderListener
{
    /**
     * @var EntityAwareGeneratorInterface
     */
    protected $idGenerator;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param EntityAwareGeneratorInterface $idGenerator
     * @param WebsiteManager $websiteManager
     */
    public function __construct(EntityAwareGeneratorInterface $idGenerator, WebsiteManager $websiteManager)
    {
        $this->idGenerator = $idGenerator;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        /** @var Order $entity */
        $entity = $args->getEntity();

        if ($entity instanceof Order) {
            $entity->setWebsite($this->websiteManager->getCurrentWebsite());
        }
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
