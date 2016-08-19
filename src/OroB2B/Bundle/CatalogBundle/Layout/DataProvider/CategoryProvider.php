<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

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

            $this->tree[$userId] =
                $rootCategory ? $this->categoryTreeProvider->getCategories($user, $rootCategory, false) : [];
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
