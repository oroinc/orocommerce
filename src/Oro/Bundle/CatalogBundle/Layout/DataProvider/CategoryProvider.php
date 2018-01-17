<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;

class CategoryProvider
{
    use DataProviderCacheTrait;

    /** @var Category[] */
    protected $categories = [];

    /** @var array */
    protected $tree = [];

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var RequestProductHandler */
    protected $requestProductHandler;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /** @var LocalizationHelper */
    protected $localizationHelper;

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
     * @deprecated use CategoryBreadcrumbProvider::getItems() instead
     * @return Category[]
     */
    public function getBreadcrumbs()
    {
        $categories = array_merge($this->getParentCategories(), [$this->getCurrentCategory()]);
        $breadcrumbs = [];

        /* @var Category $category */
        foreach ($categories as $category) {
            $breadcrumbs[] = [
                'label_localized' => $category->getTitles(),
                'route' => 'oro_product_frontend_product_index',
                'routeParams' => [
                    'categoryId' => $category->getId(),
                    'includeSubcategories' => $this->requestProductHandler->getIncludeSubcategoriesChoice()
                ]
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param CustomerUser|null $user
     *
     * @return array
     */
    public function getCategoryTreeArray(CustomerUser $user = null)
    {
        $this->initCache([
            'category',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalization()
        ]);

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
            $parents = $this->categoryRepository->getPath($parent);
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
                $this->categories[$categoryId] = $this->categoryRepository->find($categoryId);
            } else {
                $this->categories[$categoryId] = $this->categoryRepository->getMasterCatalogRoot();
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
}
