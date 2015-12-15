<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

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
class CategoryVisibilityCacheClearAction extends AbstractAction
{
    /**
     * @var string
     */
    protected $entity;

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
        if (!$context instanceof ProcessData) {
            throw new \LogicException('This action can be called only from process context');
        }

        if ($this->entity) {
            $entity = $this->contextAccessor->getValue($context, $this->entity);
        } else {
            $entity = $context->getEntity();
        }

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

        if (isset($options['entity'])) {
            $this->entity = $options['entity'];
        } elseif (isset($options[0])) {
            $this->entity = $options[0];
        }

        return $this;
    }
}
