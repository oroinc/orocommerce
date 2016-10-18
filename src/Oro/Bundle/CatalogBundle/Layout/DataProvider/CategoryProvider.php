<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryProvider
{
    /** @var array */
    protected $categories = [];

    /** @var array */
    protected $tree = [];

    /** @var CategoryRepository  */
    protected $categoryRepository;

    /** @var RequestProductHandler  */
    protected $requestProductHandler;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param CategoryRepository $categoryRepository
     * @param CategoryTreeProvider $categoryTreeProvider
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        CategoryRepository $categoryRepository,
        CategoryTreeProvider $categoryTreeProvider
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->categoryRepository = $categoryRepository;
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * @return Category
     */
    public function getCurrentCategory()
    {
        return $this->loadCategory((int) $this->requestProductHandler->getCategoryId());
    }

    /**
     * @param AccountUser|null $user
     *
     * @return Category[]
     */
    public function getCategoryTree(AccountUser $user = null)
    {
        $userId = $user ? $user->getId() : 0;
        if (!array_key_exists($userId, $this->tree)) {
            $rootCategory = $this->loadCategory();

            $categories = $rootCategory ? $this->categoryTreeProvider->getCategories($user, $rootCategory, false) : [];
            $this->tree[$userId] = array_filter($categories, function (Category $category) use ($rootCategory) {
                return $category->getParentCategory() === $rootCategory;
            });
        }

        return $this->tree[$userId];
    }

    /**
     * @param int $categoryId
     *
     * @return Category
     */
    protected function loadCategory($categoryId = 0)
    {
        if (!array_key_exists($categoryId, $this->categories)) {
            if ($categoryId) {
                $this->categories[$categoryId] = $this->categoryRepository->find($categoryId);
            } else {
                $this->categories[$categoryId] = $this->categoryRepository->getMasterCatalogRoot();
            }
        }

        return $this->categories[$categoryId];
    }
}
