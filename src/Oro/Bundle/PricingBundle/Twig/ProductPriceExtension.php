<?php

namespace Oro\Bundle\PricingBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions related to product prices:
 *   - is_price_hidden
 */
class ProductPriceExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('is_price_hidden', [$this, 'isPriceHidden']),
        ];
    }

    /**
     * Checks if prices should be hidden for given product.
     */
    public function isPriceHidden(mixed $product, bool $applicableForConfiguredProduct = false): bool
    {
        $productType = null;

        if ($product instanceof Product) {
            $productType = $product->getType();
        } elseif ($product instanceof ProductView && $product->has('type')) {
            $productType = $product->get('type');
        } elseif (is_array($product) && isset($product['type'])) { // Search result item
            $productType = $product['type'];
        }

        return match ($productType) {
            Product::TYPE_KIT, null => true,
            Product::TYPE_CONFIGURABLE => !$applicableForConfiguredProduct,
            default => false,
        };
    }
}
