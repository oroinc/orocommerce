<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Url provider for Category entity.
 */
class CategoryContextUrlProvider implements ContextUrlProviderInterface
{
    const CATEGORY_ROUTE_NAME = 'oro_product_frontend_product_index';
    const USED_SLUG_KEY = '_used_slug';
    const CATEGORY_ID = 'categoryId';
    const INCLUDE_SUBCATEGORIES = 'includeSubcategories';

    private RequestStack $requestStack;

    private UrlCacheInterface $cache;

    private LocalizationProviderInterface $localizationProvider;

    public function __construct(
        RequestStack $requestStack,
        UrlCacheInterface $cache,
        LocalizationProviderInterface $localizationProvider
    ) {
        $this->requestStack = $requestStack;
        $this->cache = $cache;
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($data)
    {
        $request = $this->requestStack->getCurrentRequest();
        $contextUrl = null;
        if ($request && $request->attributes->has(self::USED_SLUG_KEY)) {
            $slug = $request->attributes->get(self::USED_SLUG_KEY);
            if ($slug instanceof Slug && $slug->getRouteName() === self::CATEGORY_ROUTE_NAME) {
                if ($slug->getRouteParameters()[self::CATEGORY_ID] == $data) {
                    $contextUrl = $slug->getUrl();
                }
            }
        }
        if (!$contextUrl) {
            $localizationId = null;
            if ($localization = $this->localizationProvider->getCurrentLocalization()) {
                $localizationId = $localization->getId();
            }

            $contextUrl = $this->cache->getUrl(
                self::CATEGORY_ROUTE_NAME,
                [self::CATEGORY_ID => $data, self::INCLUDE_SUBCATEGORIES => true],
                $localizationId
            );
        }

        return $contextUrl;
    }
}
