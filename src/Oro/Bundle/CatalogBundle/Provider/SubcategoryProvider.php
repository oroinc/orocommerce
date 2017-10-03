<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SubcategoryProvider
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var CategoryTreeProvider */
    protected $categoryProvider;

    /**
     * @param RequestStack $requestStack
     * @param TokenAccessorInterface $tokenAccessor
     * @param CategoryRepository $categoryRepository
     * @param CategoryTreeProvider $categoryProvider
     */
    public function __construct(
        RequestStack $requestStack,
        TokenAccessorInterface $tokenAccessor,
        CategoryRepository $categoryRepository,
        CategoryTreeProvider $categoryProvider
    ) {
        $this->requestStack = $requestStack;
        $this->tokenAccessor = $tokenAccessor;
        $this->categoryRepository = $categoryRepository;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @return null|Category
     */
    public function getCurrentCategory()
    {
        return $this->getCategoryFromRequest();
    }

    /**
     * @return array|Category[]
     */
    public function getAvailableSubcategories()
    {
        $category = $this->getCategoryFromRequest();

        $categories = array_filter(
            $this->categoryProvider->getCategories($this->tokenAccessor->getUser(), $category, false),
            function (Category $item) use ($category) {
                return count($item->getProducts()) > 0 && $item->getParentCategory()->getId() === $category->getId();
            }
        );

        return $categories;
    }

    /**
     * @return null|Category
     */
    protected function getCategoryFromRequest()
    {
        $categoryId = $this->requestStack->getCurrentRequest()->get('categoryId');

        return $this->categoryRepository->find($categoryId);
    }
}
