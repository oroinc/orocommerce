<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityListener
{
    /**
     * @var CategoryVisibilityStorage
     */
    protected $categoryVisibilityStorage;

    /**
     * @var bool
     */
    protected $invalidateAll = false;

    /**
     * @var bool
     */
    protected $flushCounter = 0;

    public function __construct(CategoryVisibilityStorage $categoryVisibilityStorage)
    {
        $this->categoryVisibilityStorage = $categoryVisibilityStorage;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        if (!$args->getEntity() instanceof Category) {
            return;
        }

        $this->invalidateAll = true;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Category) {
            return;
        }

        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (isset($changeSet['parentCategory'])) {
            $this->invalidateAll = true;
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        if (!$args->getEntity() instanceof Category) {
            return;
        }

        $this->invalidateAll = true;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->flushCounter++;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $this->flushCounter--;
        if ($this->flushCounter === 0 && $this->invalidateAll) {
            $this->categoryVisibilityStorage->clearData();
            $this->reset();
        }
    }

    /**
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        $this->reset();
    }

    protected function reset()
    {
        $this->invalidateAll = false;
    }
}
