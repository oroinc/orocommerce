<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

/**
 * Provides Featured Category data for layouts
 */
class FeaturedCategoriesProvider
{
    /** @var CategoriesProvider */
    private $categoryTreeProvider;

    /** @var TokenAccessor */
    private $tokenAccessor;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var CacheProvider */
    private $cache;

    /** @var int */
    private $cacheLifeTime;

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
     * @param CacheProvider $cache
     * @param int           $lifeTime
     */
    public function setCache(CacheProvider $cache, $lifeTime = 0)
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @param array $categoryIds
     *
     * @return array [['id' => id, 'title' => title, 'short' => short, 'small_image' => image], ...]
     */
    public function getAll(array $categoryIds = [])
    {
        $user = $this->getCurrentUser();
        $cacheKey = $this->getCacheKey($categoryIds, $user);

        $result = $this->cache->fetch($cacheKey);
        if (false !== $result) {
            return $result;
        }

        $result = [];
        $categories = $this->categoryTreeProvider->getCategories($user);
        foreach ($categories as $category) {
            if ($category->getLevel() !== 0 && (!$categoryIds || in_array($category->getId(), $categoryIds, true))) {
                $result[] = [
                    'id' => $category->getId(),
                    'title' => (string) $this->localizationHelper->getLocalizedValue($category->getTitles()),
                    'short' => (string) $this->localizationHelper->getLocalizedValue($category->getShortDescriptions()),
                    'small_image' => $category->getSmallImage(),
                ];
            }
        }
        $this->cache->save($cacheKey, $result, $this->cacheLifeTime);

        return $result;
    }

    /**
     * @param int[]             $categoryIds
     * @param CustomerUser|null $user
     *
     * @return string
     */
    private function getCacheKey(array $categoryIds, ?CustomerUser $user): string
    {
        $customer = $user ? $user->getCustomer() : null;
        $customerGroup = $customer ? $customer->getGroup() : null;

        return sprintf(
            'featured_categories_%s_%s_%s_%s_%s_%s',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalizationId(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            implode('_', $categoryIds),
            $this->tokenAccessor->getOrganization()->getId()
        );
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
        $localization = $this->localizationHelper->getCurrentLocalization();

        return $localization ? $localization->getId() : 0;
    }
}
