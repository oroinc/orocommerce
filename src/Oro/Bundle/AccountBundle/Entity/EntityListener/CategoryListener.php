<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageHandler;

class CategoryListener
{
    const FIELD_PARENT_CATEGORY = 'parentCategory';

    /**
     * @var CategoryMessageHandler
     */
    protected $categoryMessageHandler;

    /**
     * @param CategoryMessageHandler $categoryMessageHandler
     */
    public function __construct(
        CategoryMessageHandler $categoryMessageHandler

    ) {
        $this->categoryMessageHandler = $categoryMessageHandler;
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_PARENT_CATEGORY)) {
            $this->categoryMessageHandler->addCategoryMessageToSchedule(
                'orob2b_account.visibility.change_category_visibility',
                $category
            );
        }
    }

    public function postRemove()
    {
        $this->categoryMessageHandler->addCategoryMessageToSchedule('orob2b_account.visibility.category_remove');
    }
}
