<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
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
     * @var array
     */
    protected $invalidateAccountIds = [];

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
        $entity = $args->getEntity();

        if ($entity instanceof Category || $entity instanceof CategoryVisibility) {
            $this->invalidateAll = true;
        } elseif (!$this->invalidateAll) {
            $this->collectInvalidateAccounts($entity, $args);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Category) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
            if (isset($changeSet['parentCategory'])) {
                $this->invalidateAll = true;
            }
        } elseif ($entity instanceof CategoryVisibility) {
            $this->invalidateAll = true;
        } elseif (!$this->invalidateAll) {
            $this->collectInvalidateAccounts($entity, $args);
        }
    }

    /**
     * @param $entity
     * @param LifecycleEventArgs $args
     */
    protected function collectInvalidateAccounts($entity, LifecycleEventArgs $args)
    {
        if ($entity instanceof Account) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
            if (isset($changeSet['group'])) {
                $this->invalidateAccountIds[] = $entity->getId();
            }
        } elseif ($entity instanceof AccountCategoryVisibility && $entity->getAccount()) {
            $this->invalidateAccountIds[] = $entity->getAccount()->getId();
        } elseif ($entity instanceof AccountGroupCategoryVisibility && $entity->getAccountGroup()) {
            $groupAccounts = $entity->getAccountGroup()->getAccounts();
            foreach ($groupAccounts as $account) {
                $this->invalidateAccountIds[] = $account->getId();
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof Category) {
            $this->invalidateAll = true;
        }
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

        if ($this->flushCounter === 0) {
            if ($this->invalidateAll) {
                $this->categoryVisibilityStorage->clearData();
                $this->reset();
            } elseif (count($this->invalidateAccountIds)) {
                $this->categoryVisibilityStorage->clearData($this->invalidateAccountIds);
                $this->invalidateAccountIds = [];
            }
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
