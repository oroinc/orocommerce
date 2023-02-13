<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides Category data for layouts
 */
class CategoryProvider
{
    private $categories = [];
    private ManagerRegistry $registry;
    private RequestProductHandler $requestProductHandler;
    private CategoryTreeProvider $categoryTreeProvider;
    private TokenAccessorInterface $tokenAccessor;
    private MasterCatalogRootProviderInterface $masterCatalogRootProvider;

    public function __construct(
        RequestProductHandler $requestProductHandler,
        ManagerRegistry $registry,
        CategoryTreeProvider $categoryTreeProvider,
        TokenAccessorInterface $tokenAccessor,
        MasterCatalogRootProviderInterface $masterCatalogRootProvider
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->registry = $registry;
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
    }

    public function getCurrentCategory(): ?Category
    {
        return $this->loadCategory($this->requestProductHandler->getCategoryId());
    }

    public function getIncludeSubcategoriesChoice(bool $defaultValue = null): bool
    {
        return $this->requestProductHandler->getIncludeSubcategoriesChoice($defaultValue);
    }

    /**
     * @return Category[]
     */
    public function getCategoryPath(): array
    {
        return $this->categoryTreeProvider->getParentCategories($this->getCustomerUser(), $this->getCurrentCategory());
    }

    protected function loadCategory(int $categoryId = 0): ?Category
    {
        if (!array_key_exists($categoryId, $this->categories)) {
            if ($categoryId) {
                $this->categories[$categoryId] = $this->getCategoryRepository()->find($categoryId);
            } else {
                $this->categories[$categoryId] = $this->masterCatalogRootProvider->getMasterCatalogRoot();
            }
        }

        return $this->categories[$categoryId];
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->registry->getManagerForClass(Category::class)->getRepository(Category::class);
    }


    private function getCustomerUser(): ?CustomerUser
    {
        $token = $this->tokenAccessor->getToken();

        return $token?->getUser() instanceof CustomerUser ? $token->getUser() : null;
    }
}
