<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoryProvider;

class CategoryTreeProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @param CategoryProvider $categoryProvider
     */
    public function __construct(CategoryProvider $categoryProvider)
    {
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var AccountUser $user */
        $user = $context->get('logged_user');
        $userId = $user ? $user->getId() : null;

        if (!array_key_exists($userId, $this->data)) {
            $categories = $this->categoryProvider->getCategories($user, null, false);
            $rootCategory = $this->findRootCategory($categories);

            $this->data[$userId] = [
                'all' => $categories,
                'main' => $rootCategory ? $this->categoryProvider->getCategories($user, $rootCategory, false) : [],
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
