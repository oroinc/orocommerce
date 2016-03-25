<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryTreeProviderProvider extends AbstractServerRenderDataProvider
{
    /** @var array */
    protected $data;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /**
     * @param CategoryTreeProvider $categoryTreeProvider
     */
    public function __construct(CategoryTreeProvider $categoryTreeProvider)
    {
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var AccountUser $user */
        $user = $context->get('logged_user');
        $userId = $user ? $user->getId() : null;

        if (!$this->data[$userId]) {
            $categories = $this->categoryTreeProvider->getCategories($user, null, null);
            $rootCategory = $this->findRootCategory($categories);

            $this->data[$userId] = [
                'all' => $categories,
                'main' => $rootCategory ? $rootCategory->getChildCategories()->toArray() : [],
            ];
        }

        return $this->data[$userId];
    }

    /**
     * @param Category[] $categories
     * @return Category|null
     */
    protected function findRootCategory($categories)
    {
        $rootCategory = null;
        foreach ($categories as $category) {
            if ($category->getLevel() === 0) {
                $rootCategory = $category;
                break;
            }
        }
        return $rootCategory;
    }
}
