<?php

namespace OroB2B\src\Oro\Bundle\B2BEntityBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\src\Oro\Bundle\B2BEntityBundle\Storage\ExtraInsertEntityStorageInterface;

class DoctrinePostFlushListener
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var ExtraInsertEntityStorageInterface
     */
    protected $storage;

    /**
     * @param RegistryInterface $registry
     * @param ExtraInsertEntityStorageInterface $storage
     */
    public function __construct(RegistryInterface $registry, ExtraInsertEntityStorageInterface $storage)
    {
        $this->registry = $registry;
        $this->storage = $storage;
    }

    /**
     * Save collected changes
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        return;
    }
}
