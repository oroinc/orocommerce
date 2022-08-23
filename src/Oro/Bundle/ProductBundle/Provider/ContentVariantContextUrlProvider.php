<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Url provider for ContentVariant entity.
 */
class ContentVariantContextUrlProvider implements ContextUrlProviderInterface
{
    const USED_SLUG_KEY = '_used_slug';

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
        $contextUrl = $this->getContextUrlFromRequest($data);

        if (!$contextUrl) {
            $localizationId = null;
            if ($localization = $this->localizationProvider->getCurrentLocalization()) {
                $localizationId = $localization->getId();
            }

            $contextUrl = $this->cache->getUrl(
                ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME,
                [ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => $data],
                $localizationId
            );
        }

        return $contextUrl;
    }

    /**
     * @param mixed $data
     * @return null|string
     */
    private function getContextUrlFromRequest($data)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->attributes->has(self::USED_SLUG_KEY)) {
            return null;
        }

        $slug = $request->attributes->get(self::USED_SLUG_KEY);
        if (!$slug instanceof Slug) {
            return null;
        }

        if ($slug->getRouteName() !== ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME) {
            return null;
        }

        $parameters = $slug->getRouteParameters();
        if (!isset($parameters[ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY])) {
            return null;
        }

        if ($parameters[ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY] != $data) {
            return null;
        }

        return $slug->getUrl();
    }
}
