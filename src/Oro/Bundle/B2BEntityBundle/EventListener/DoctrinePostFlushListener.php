<?php

namespace Oro\Bundle\B2BEntityBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

class DoctrinePostFlushListener implements OptionalListenerInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var ObjectManager[]
     */
    protected $managers = [];

    /**
     * @param RegistryInterface $registry
     * @param ExtraActionEntityStorageInterface $storage
     */
    public function __construct(RegistryInterface $registry, ExtraActionEntityStorageInterface $storage)
    {
        $this->registry = $registry;
        $this->storage = $storage;
    }

    public function postFlush()
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->storage->hasScheduledForInsert()) {
            foreach ($this->storage->getScheduledForInsert() as $entity) {
                $em = $this->getEntityManager($entity);
                $em->persist($entity);
            }
            $this->storage->clearScheduledForInsert();

            foreach ($this->managers as $em) {
                $em->flush();
            }
        }
    }

    /**
     * @param $entity
     * @return ObjectManager
     */
    protected function getEntityManager($entity)
    {
        $entityClassName = get_class($entity);

        if (!array_key_exists($entityClassName, $this->managers)) {
            $this->managers[$entityClassName] = $this->registry->getManagerForClass($entityClassName);
        }

        return $this->managers[$entityClassName];
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }
}
