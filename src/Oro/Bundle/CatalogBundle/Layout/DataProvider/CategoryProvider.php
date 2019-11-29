<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;

/**
 * Provides Category data for layouts
 */
class CategoryProvider
{
    use DataProviderCacheTrait;

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

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param RequestProductHandler  $requestProductHandler
     * @param ManagerRegistry        $registry
     * @param CategoryTreeProvider   $categoryTreeProvider
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        ManagerRegistry $registry,
        CategoryTreeProvider $categoryTreeProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->registry = $registry;
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function setLocalizationHelper(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
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
        $customer = $user ? $user->getCustomer() : null;
        $customerGroup = $customer ? $customer->getGroup() : null;
        $currentOrganization = $this->tokenAccessor->getOrganization();

        $this->initCache([
            'category',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalization(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            $currentOrganization ? $currentOrganization->getId() : 0
        ]);

        return $this->fetchFromCache($user);
    }

    /**
     * @param CustomerUser|null $user
     * @return array|false
     */
    private function fetchFromCache(CustomerUser $user = null)
    {
        $useCache = $this->isCacheUsed();

        if (true === $useCache) {
            $result = $this->getFromCache();
            if ($result) {
                return $result;
            }
        }

        $result = $this->categoryTreeToArray(
            $this->getCategoryTree($user)
        );

        if (true === $useCache) {
            $this->saveToCache($result);
        }

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
                $this->categories[$categoryId] = $this->getCurrentMasterCatalogRoot();
            }
        }

        return $this->categories[$categoryId];
    }

    /**
     * @return int
     */
    protected function getCurrentLocalization()
    {
        $localization_id = ($this->localizationHelper && $this->localizationHelper->getCurrentLocalization()) ?
            $this->localizationHelper->getCurrentLocalization()->getId() : 0;

        return $localization_id;
    }

    /**
     * @return Category|null
     */
    private function getCurrentMasterCatalogRoot()
    {
        $organization = $this->tokenAccessor->getOrganization();

        return $organization ? $this->getCategoryRepository()->getMasterCatalogRoot($organization) : null;
    }

    /**
     * @return CategoryRepository
     */
    private function getCategoryRepository(): CategoryRepository
    {
        return $this->registry->getManagerForClass(Category::class)->getRepository(Category::class);
    }
}
