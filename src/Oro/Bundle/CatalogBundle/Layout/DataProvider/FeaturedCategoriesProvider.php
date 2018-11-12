<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
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
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param CategoriesProvider $categoryTreeProvider
     * @param TokenStorageInterface $tokenStorage
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        CategoriesProvider $categoryTreeProvider,
        TokenStorageInterface $tokenStorage,
        LocalizationHelper $localizationHelper
    ) {
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenStorage = $tokenStorage;
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

        $this->initCache([
            'featured_categories',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalizationId(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            implode('_', $categoryIds),
        ]);

        return $this->getCategories($categoryIds);
    }

    /**
     * Retrieve data in format [['id' => %d, 'title' => %s, 'small_image' => %s], [...], ...]
     *
     * @param array $categoryIds
     * @return Category[]
     */
    private function getCategories(array $categoryIds = [])
    {
        $useCache = $this->isCacheUsed();
        if (true === $useCache) {
            $result = $this->getFromCache();
            if ($result) {
                return $result;
            }
        }

        $data = [];

        $categories = $this->categoryTreeProvider->getCategories($this->getCurrentUser());
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
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof CustomerUser) {
            return $token->getUser();
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
