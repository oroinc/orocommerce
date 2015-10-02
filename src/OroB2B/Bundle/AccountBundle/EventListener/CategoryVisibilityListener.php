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

    /**
     * @param CategoryVisibilityStorage $categoryVisibilityStorage
     */
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
            $this->collectInvalidateAccountIds($args);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (($entity instanceof Category && $this->hasChangeSetProperty($args, 'parentCategory')) ||
            $entity instanceof CategoryVisibility
        ) {
            $this->invalidateAll = true;
        } elseif (!$this->invalidateAll) {
            $this->collectInvalidateAccountIds($args);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Category || $entity instanceof CategoryVisibility) {
            $this->invalidateAll = true;
        } elseif (!$this->invalidateAll) {
            $this->collectInvalidateAccountIds($args, false);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param string $propertyName
     * @return bool
     */
    protected function hasChangeSetProperty(LifecycleEventArgs $args, $propertyName)
    {
        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($args->getEntity());

        return isset($changeSet[$propertyName]);
    }

    /**
     * @param LifecycleEventArgs $args
     * @param bool $checkAccountChangeSet
     */
    protected function collectInvalidateAccountIds(LifecycleEventArgs $args, $checkAccountChangeSet = true)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Account && (!$checkAccountChangeSet || $this->hasChangeSetProperty($args, 'group'))) {
            $this->invalidateAccountIds[] = $entity->getId();
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
                $this->reset();
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
        $this->invalidateAccountIds = [];
    }
}
