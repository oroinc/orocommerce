<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FeaturedCategoriesProvider
{
    /**
     * @var Category[]
     */
    protected $categories;

    /**
     * @var CategoriesProvider
     */
    protected $categoryTreeProvider;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param CategoriesProvider $categoryTreeProvider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(CategoriesProvider $categoryTreeProvider, TokenStorageInterface $tokenStorage)
    {
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $categoryIds
     * @return Category[]
     */
    public function getAll(array $categoryIds = [])
    {
        $this->setCategories($categoryIds);

        return $this->categories;
    }

    /**
     * @param array $categoryIds
     */
    protected function setCategories(array $categoryIds = [])
    {
        if ($this->categories !== null) {
            return;
        }

        $categories = $this->categoryTreeProvider->getCategories($this->getCurrentUser());
        $this->categories = array_filter(
            $categories,
            function (Category $category) use ($categoryIds) {
                if ($categoryIds && !in_array($category->getId(), $categoryIds, true)) {
                    return false;
                }

                return $category->getLevel() !== 0;
            }
        );
    }

    /**
     * @return CustomerUser|null
     */
    protected function getCurrentUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof CustomerUser) {
            return $token->getUser();
        }

        return null;
    }
}
