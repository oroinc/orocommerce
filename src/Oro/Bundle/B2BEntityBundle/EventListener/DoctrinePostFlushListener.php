<?php

namespace Oro\Bundle\B2BEntityBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

class DoctrinePostFlushListener implements OptionalListenerInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExtraActionEntityStorageInterface $storage
     */
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

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }
}
