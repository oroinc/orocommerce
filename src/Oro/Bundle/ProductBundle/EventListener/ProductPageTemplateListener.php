<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;

/**
 * Set theme configuration value as page template for product
 */
class ProductPageTemplateListener
{
    public function prePersist(Product $product, LifecycleEventArgs $args): void
    {
        if (!$product->getPageTemplate()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(ThemeConfigurationFallbackProvider::FALLBACK_ID);
            $product->setPageTemplate($entityFallback);
        }
    }
}
