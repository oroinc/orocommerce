<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides Category data for layouts
 */
class CategoryProvider
{
    private $categories = [];
    private $tree = [];
    private ManagerRegistry $registry;
    private RequestProductHandler $requestProductHandler;
    private CategoryTreeProvider $categoryTreeProvider;
    private TokenAccessorInterface $tokenAccessor;
    private LocalizationHelper $localizationHelper;
    private CacheInterface $cache;
    private int $cacheLifeTime;
    private MasterCatalogRootProviderInterface $masterCatalogRootProvider;

    public function __construct(
        RequestProductHandler $requestProductHandler,
        ManagerRegistry $registry,
        CategoryTreeProvider $categoryTreeProvider,
        TokenAccessorInterface $tokenAccessor,
        LocalizationHelper $localizationHelper,
        MasterCatalogRootProviderInterface $masterCatalogRootProvider
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->registry = $registry;
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->localizationHelper = $localizationHelper;
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
    }

    public function setCache(CacheInterface $cache, int $lifeTime = 0) : void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @return Category
     */
    public function getCurrentCategory()
    {
        return $this->loadCategory($this->requestProductHandler->getCategoryId());
    }

    /**
     * @return Category
     */
    public function getRootCategory()
    {
        return $this->loadCategory();
    }

    public function getCategoryTreeArray(CustomerUser $user = null) : array
    {
        $cacheKey = $this->getCacheKey($user);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter($this->cacheLifeTime);
            return $this->categoryTreeToArray($this->getCategoryTree($user));
        });
    }

    /**
     * @param CustomerUser|null $user
     *
     * @return Category[]
     */
    public function getCategoryTree(CustomerUser $user = null)
    {
        $userId = $user ? $user->getId() : 0;
        if (!array_key_exists($userId, $this->tree)) {
            $rootCategory = $this->loadCategory();

            $this->tree[$userId] = [];
            if ($rootCategory) {
                /** @var DTO\Category[] $categoryDTOs */
                $categoryDTOs = [];
                $categoryDTOs[$rootCategory->getMaterializedPath()] = new DTO\Category($rootCategory);
                $categories = $this->categoryTreeProvider->getCategories($user, $rootCategory, false);
                $categoryIds = array_map(static fn (Category $category) => $category->getId(), $categories);
                $categoryIds[] = $rootCategory->getId();
                foreach ($categories as $category) {
                    $dto = new DTO\Category($category);
                    $categoryDTOs[$category->getMaterializedPath()] = $dto;
                    $parentCategory = $this->getParentCategoryRecursive($category, $categoryIds);
                    if ($parentCategory) {
                        $categoryDTOs[$parentCategory->getMaterializedPath()]
                            ->addChildCategory($dto);
                    }
                }

                $this->tree[$userId] = $categoryDTOs[$rootCategory->getMaterializedPath()]->getChildCategories();
                unset($categoryDTOs);
            }
        }

        return $this->tree[$userId];
    }

    public function getIncludeSubcategoriesChoice(bool $defaultValue = null): bool
    {
        return $this->requestProductHandler->getIncludeSubcategoriesChoice($defaultValue);
    }

    /**
     * @return array
     */
    public function getParentCategories()
    {
        // we don't need current category in the path, so let's start from parent category
        $parent = $this->getCurrentCategory()->getParentCategory();

        if ($parent !== null) {
            $parents = $this->getCategoryRepository()->getPath($parent);

            return is_array($parents) ? $parents : [];
        }

        return [];
    }

    /**
     * @return Category[]
     */
    public function getCategoryPath(): array
    {
        return $this->categoryTreeProvider->getParentCategories($this->getCustomerUser(), $this->getCurrentCategory());
    }

    /**
     * @param ArrayCollection|Category[] $items
     * @return array
     */
    protected function categoryTreeToArray(ArrayCollection $items)
    {
        $data = [];

        /** @var DTO\Category[] $item */
        foreach ($items as $key => $item) {
            $children = $this->categoryTreeToArray($item->getChildCategories());

            $data[$key] = [
                'id' => $item->id(),
                'title' =>
                    $this->localizationHelper
                        ->getLocalizedValue($item->titles())
                        ->getString(),
                'hasSublist' => count($children),
                'childCategories' => $children
            ];
        }

        return $data;
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
                $this->categories[$categoryId] = $this->getCategoryRepository()->find($categoryId);
            } else {
                $this->categories[$categoryId] = $this->masterCatalogRootProvider->getMasterCatalogRoot();
            }
        }

        return $this->categories[$categoryId];
    }

    /**
     * @return int
     */
    protected function getCurrentLocalization()
    {
        $localization = $this->localizationHelper->getCurrentLocalization();

        return $localization ? $localization->getId() : 0;
    }

    protected function getCacheKey(?CustomerUser $user): string
    {
        $customer = $user ? $user->getCustomer() : null;
        $customerGroup = $customer ? $customer->getGroup() : null;
        $currentOrganization = $this->tokenAccessor->getOrganization();

        return sprintf(
            'category_%s_%s_%s_%s_%s',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalization(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            $currentOrganization ? $currentOrganization->getId() : 0
        );
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

    private function getParentCategoryRecursive(Category $category, array $availableCategoryIds): ?Category
    {
        $parentCategory = $category->getParentCategory();
        if (!$parentCategory) {
            return null;
        }

        if (in_array($parentCategory->getId(), $availableCategoryIds, true)) {
            return $parentCategory;
        }

        return $this->getParentCategoryRecursive($parentCategory, $availableCategoryIds);
    }
}
