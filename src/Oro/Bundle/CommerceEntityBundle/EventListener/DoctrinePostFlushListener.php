<?php

namespace Oro\Bundle\CommerceEntityBundle\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Write additional changes from ExtraActionEntityStorageInterface
 */
class DoctrinePostFlushListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var \SplObjectStorage|ObjectManager[]
     */
    protected $managers;

    public function __construct(DoctrineHelper $doctrineHelper, ExtraActionEntityStorageInterface $storage)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->storage = $storage;
        $this->managers = new \SplObjectStorage();
    }

    public function postFlush()
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->storage->getScheduledForInsert()) {
            foreach ($this->storage->getScheduledForInsert() as $entitiesByClass) {
                foreach ($entitiesByClass as $entity) {
                    $em = $this->getEntityManager($entity);
                    $em->persist($entity);
                }
            }
            $this->storage->clearScheduledForInsert();

            foreach ($this->managers as $em) {
                $em->flush();
            }
        }
    }

    /**
     * @param object $entity
     * @return ObjectManager
     */
    protected function getEntityManager($entity)
    {
        $em = $this->doctrineHelper->getEntityManager($entity);
        $this->managers->attach($em);

        return $em;
    }
}
