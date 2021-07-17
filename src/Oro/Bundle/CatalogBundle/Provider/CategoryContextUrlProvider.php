<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
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

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlCacheInterface
     */
    private $cache;

    /**
     * @var UserLocalizationManagerInterface
     */
    private $userLocalizationManager;

    public function __construct(
        RequestStack $requestStack,
        UrlCacheInterface $cache,
        UserLocalizationManagerInterface $userLocalizationManager
    ) {
        $this->requestStack = $requestStack;
        $this->cache = $cache;
        $this->userLocalizationManager = $userLocalizationManager;
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
            if ($localization = $this->userLocalizationManager->getCurrentLocalization()) {
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
