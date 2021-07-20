<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
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

/**
 * Provides Category data for layouts
 */
class CategoryProvider
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

    /** @var CacheProvider */
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
     * @param CacheProvider $cache
     * @param int           $lifeTime
     */
    public function setCache(CacheProvider $cache, $lifeTime = 0)
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @return Category
     */
    public function getCurrentCategory()
    {
        return $this->loadCategory((int)$this->requestProductHandler->getCategoryId());
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

        $result = $this->cache->fetch($cacheKey);
        if (false !== $result) {
            return $result;
        }

        $result = $this->categoryTreeToArray($this->getCategoryTree($user));
        $this->cache->save($cacheKey, $result, $this->cacheLifeTime);

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
                foreach ($categories as $category) {
                    $dto = new DTO\Category($category);
                    $categoryDTOs[$category->getMaterializedPath()] = $dto;
                    if ($category->getParentCategory()) {
                        $categoryDTOs[$category->getParentCategory()->getMaterializedPath()]
                            ->addChildCategory($dto);
                    }
                }

                $this->tree[$userId] = $categoryDTOs[$rootCategory->getMaterializedPath()]->getChildCategories();
                unset($categoryDTOs);
            }
        }

        return $this->tree[$userId];
    }

    /**
     * @param null|bool $defaultValue
     * @return bool
     */
    public function getIncludeSubcategoriesChoice($defaultValue = null)
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
        } else {
            return [];
        }
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
}
