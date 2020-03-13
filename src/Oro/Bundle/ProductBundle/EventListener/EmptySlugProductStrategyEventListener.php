<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

/**
 * On product import checks if the slug is empty and generates one from the product name
 */
class EmptySlugProductStrategyEventListener
{
    /** @var SlugGenerator */
    private $slugGenerator;

    /**
     * @param SlugGenerator $slugGenerator
     */
    public function __construct(SlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @param ProductStrategyEvent $event
     */
    public function onProcessAfter(ProductStrategyEvent $event): void
    {
        $product = $event->getProduct();

        if ($product->getSlugPrototypes()->isEmpty()) {
            foreach ($product->getNames() as $localizedName) {
                $this->addSlug($product, $localizedName);
            }
        }

        if (!$product->getDefaultSlugPrototype() && $product->getDefaultName()) {
            $this->addSlug($product, $product->getDefaultName());
        }
    }

    /**
     * @param Product $product
     * @param ProductName $localizedName
     */
    private function addSlug(Product $product, ProductName $localizedName): void
    {
        $localizedSlug = LocalizedFallbackValue::createFromAbstract($localizedName);
        $localizedSlug->setString($this->slugGenerator->slugify($localizedSlug->getString()));

        $product->addSlugPrototype($localizedSlug);
    }
}
