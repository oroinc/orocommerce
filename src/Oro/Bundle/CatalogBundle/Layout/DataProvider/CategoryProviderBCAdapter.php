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
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Provides Category data for layouts.
 * Component added back for theme layout BC from version 5.0
 * This class was added while migrating from 5.0, it didn't exist in previous versions
 */
class CategoryProviderBCAdapter
{
    /** @var Category[] */
    protected $categories = [];

    /** @var array */
    protected $tree = [];

    /** @var ManagerRegistry */
    protected $registry;

    /** @var RequestProductHandler */
    protected $requestProductHandler;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var AdapterInterface */
    protected $cache;

    /** @var int */
    protected $cacheLifeTime;

    /** @var MasterCatalogRootProviderInterface */
    private $masterCatalogRootProvider;

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

    /**
     * @param AdapterInterface $cache
     * @param int           $lifeTime
     */
    public function setCache(AdapterInterface $cache, $lifeTime = 0)
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

    /**
     * @param CustomerUser|null $user
     *
     * @return array
     */
    public function getCategoryTreeArray(CustomerUser $user = null)
    {
        $cacheKey = $this->getCacheKey($user);

        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $result = $this->categoryTreeToArray($this->getCategoryTree($user));

        $cacheItem->set($result)->expiresAfter($this->cacheLifeTime);
        $this->cache->save($cacheItem);

        return $result;
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
