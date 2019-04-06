<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;

/**
 * Provides Featured Category data for layouts
 */
class FeaturedCategoriesProvider
{
    use DataProviderCacheTrait;

    /**
     * @var CategoriesProvider
     */
    private $categoryTreeProvider;

    /**
     * @var TokenAccessor
     */
    private $tokenAccessor;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param CategoriesProvider $categoryTreeProvider
     * @param TokenAccessor $tokenAccessor
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        CategoriesProvider $categoryTreeProvider,
        TokenAccessor $tokenAccessor,
        LocalizationHelper $localizationHelper
    ) {
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param array $categoryIds
     * @return Category[]
     */
    public function getAll(array $categoryIds = [])
    {
        $user = $this->getCurrentUser();
        $customer = $user ? $user->getCustomer() : null;
        $customerGroup = $customer ? $customer->getGroup() : null;
        $organization = $this->tokenAccessor->getOrganization();

        $this->initCache([
            'featured_categories',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalizationId(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            implode('_', $categoryIds),
            $organization->getId()
        ]);

        return $this->getCategories($categoryIds, $user);
    }

    /**
     * Retrieve data in format [['id' => %d, 'title' => %s, 'small_image' => %s], [...], ...]
     *
     * @param array $categoryIds
     * @param CustomerUser|null $user
     * @return Category[]
     */
    private function getCategories(array $categoryIds = [], CustomerUser $user = null)
    {
        $useCache = $this->isCacheUsed();
        if (true === $useCache) {
            $result = $this->getFromCache();
            if ($result) {
                return $result;
            }
        }

        $data = [];

        $categories = $this->categoryTreeProvider->getCategories($user);
        foreach ($categories as $category) {
            if ($category->getLevel() !== 0 && (!$categoryIds || in_array($category->getId(), $categoryIds, true))) {
                $data[] = [
                    'id' => $category->getId(),
                    'title' => (string) $this->localizationHelper->getLocalizedValue($category->getTitles()),
                    'small_image' => $category->getSmallImage(),
                ];
            }
        }

        if (true === $useCache) {
            $this->saveToCache($data);
        }

        return $data;
    }

    /**
     * @return CustomerUser|null
     */
    private function getCurrentUser()
    {
        $tokenUser = $this->tokenAccessor->getUser();
        if ($tokenUser instanceof CustomerUser) {
            return $tokenUser;
        }

        return null;
    }

    /**
     * @return int
     */
    private function getCurrentLocalizationId()
    {
        $localizationId = ($this->localizationHelper && $this->localizationHelper->getCurrentLocalization()) ?
            $this->localizationHelper->getCurrentLocalization()->getId() : 0;

        return $localizationId;
    }
}
