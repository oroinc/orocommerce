<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Provides {@see PageTemplate} by Product and route name.
 */
class PageTemplateProvider
{
    public const PRODUCT_DETAILS_PAGE_TEMPLATE_ROUTE_NAME = 'oro_product_frontend_product_view';

    public function __construct(
        private ThemeManager $themeManager,
        private EntityFallbackResolver $fallbackResolver
    ) {
    }

    public function getPageTemplate(Product $product, string $routeName): ?PageTemplate
    {
        $pageTemplateFallbackValue = $this->fallbackResolver->getFallbackValue($product, 'pageTemplate');
        if (!isset($pageTemplateFallbackValue[$routeName])) {
            if (is_array($pageTemplateFallbackValue) && \count($pageTemplateFallbackValue) === 1) {
                $pageTemplateFallbackValue = [
                    self::PRODUCT_DETAILS_PAGE_TEMPLATE_ROUTE_NAME => \reset($pageTemplateFallbackValue),
                ];
            } else {
                return null;
            }
        }

        foreach ($this->themeManager->getAllThemes() as $theme) {
            $pageTemplate = $theme->getPageTemplate($pageTemplateFallbackValue[$routeName], $routeName);

            if ($pageTemplate) {
                return $pageTemplate;
            }
        }

        return null;
    }
}
