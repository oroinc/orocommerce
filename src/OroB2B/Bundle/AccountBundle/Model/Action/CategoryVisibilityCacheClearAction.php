<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Clearing category visibility storage depending on context entity
 * Usage:
 * @category_visibility_cache_clear: ~
 *
 * or
 *
 * @category_visibility_cache_clear:
 *    entity: $some.path
 *
 * or
 *
 * @category_visibility_cache_clear:[$some.path]
 */
class CategoryVisibilityCacheClearAction extends AbstractEntityAwareAction
{
    /**
     * @var CategoryVisibilityStorage
     */
    protected $categoryVisibilityStorage;

    /**
     * @param CategoryVisibilityStorage $categoryVisibilityStorage
     */
    public function setCategoryVisibilityStorage(CategoryVisibilityStorage $categoryVisibilityStorage)
    {
        $this->categoryVisibilityStorage = $categoryVisibilityStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->getEntity($context);

        if ($entity instanceof Category) {
            $this->categoryVisibilityStorage->flush();
        } elseif ($entity instanceof Account) {
            $this->categoryVisibilityStorage->clearForAccount($entity);
        } elseif ($entity instanceof CategoryVisibility) {
            $this->categoryVisibilityStorage->clear();
        } elseif ($entity instanceof AccountCategoryVisibility) {
            $this->categoryVisibilityStorage->clearForAccount($entity->getAccount());
        } elseif ($entity instanceof AccountGroupCategoryVisibility) {
            $this->categoryVisibilityStorage->clearForAccountGroup($entity->getAccountGroup());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->categoryVisibilityStorage) {
            throw new \InvalidArgumentException('CategoryVisibilityStorage is not provided');
        }

        return parent::initialize($options);
    }
}
