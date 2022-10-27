<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides Featured Category data for layouts
 */
class FeaturedCategoriesProvider
{
    private CategoriesProvider $categoryTreeProvider;

    private TokenAccessor $tokenAccessor;

    private LocalizationHelper $localizationHelper;

    private WebsiteManager $websiteManager;

    private ?CacheInterface $cache = null;

    private int $cacheLifeTime = 0;

    public function __construct(
        CategoriesProvider $categoryTreeProvider,
        TokenAccessor $tokenAccessor,
        LocalizationHelper $localizationHelper,
        WebsiteManager $websiteManager
    ) {
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->localizationHelper = $localizationHelper;
        $this->websiteManager = $websiteManager;
    }

    public function setCache(CacheInterface $cache, $lifeTime = 0) : void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    public function getAll(array $categoryIds = []) : array //[['id' => id, 'title' => title, 'short' => short], ...]
    {
        $user = $this->getCurrentUser();
        $cacheKey = $this->getCacheKey($categoryIds, $user);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user, $categoryIds) {
            $item->expiresAfter($this->cacheLifeTime);
            $result = [];
            $categories = $this->categoryTreeProvider->getCategories($user);
            foreach ($categories as $category) {
                if ($category->getLevel() !== 0
                    && (!$categoryIds || in_array($category->getId(), $categoryIds, true))) {
                    $result[] = [
                        'id' => $category->getId(),
                        'title' => (string) $this->localizationHelper
                            ->getLocalizedValue($category->getTitles()),
                        'short' => (string) $this->localizationHelper
                            ->getLocalizedValue($category->getShortDescriptions()),
                        'small_image' => $category->getSmallImage(),
                    ];
                }
            }
            return $result;
        });
    }

    /**
     * @param int[] $categoryIds
     * @param CustomerUser|null $user
     *
     * @return string
     */
    private function getCacheKey(array $categoryIds, ?CustomerUser $user): string
    {
        $customer = $user?->getCustomer();
        $customerGroup = $customer?->getGroup();

        return sprintf(
            'featured_categories_%s_%s_%s_%s__%s__%s_%s',
            $user ? $user->getId() : 0,
            $this->getCurrentLocalizationId(),
            $customer ? $customer->getId() : 0,
            $customerGroup ? $customerGroup->getId() : 0,
            implode('_', $categoryIds),
            (int) $this->tokenAccessor->getOrganization()?->getId(),
            (int) $this->websiteManager->getCurrentWebsite()?->getId()
        );
    }

    private function getCurrentUser(): ?CustomerUser
    {
        $tokenUser = $this->tokenAccessor->getUser();
        if ($tokenUser instanceof CustomerUser) {
            return $tokenUser;
        }

        return null;
    }

    private function getCurrentLocalizationId(): int
    {
        return (int) $this->localizationHelper->getCurrentLocalization()?->getId();
    }
}
