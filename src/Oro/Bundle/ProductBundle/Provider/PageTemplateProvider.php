<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class PageTemplateProvider
{
    /** @var ThemeManager */
    private $themeManager;

    /** @var EntityFallbackResolver */
    private $fallBackResolver;

    public function __construct(ThemeManager $themeManager, EntityFallbackResolver $fallbackResolver)
    {
        $this->themeManager = $themeManager;
        $this->fallBackResolver = $fallbackResolver;
    }

    /**
     * @param Product $product
     * @param $routeName
     * @return null|PageTemplate
     */
    public function getPageTemplate(Product $product, $routeName)
    {
        $pageTemplateFallbackValue = $this->fallBackResolver->getFallbackValue($product, 'pageTemplate');

        if (!isset($pageTemplateFallbackValue[$routeName])) {
            return null;
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
