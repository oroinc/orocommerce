<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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
    public function onProcessAfter(ProductStrategyEvent $event)
    {
        $product = $event->getProduct();

        if ($product->getSlugPrototypes()->isEmpty()) {
            foreach ($product->getNames() as $localizedName) {
                $localizedSlug = new LocalizedFallbackValue();
                $localizedSlug->setString($this->slugGenerator->slugify($localizedName->getString()))
                    ->setFallback($localizedName->getFallback())
                    ->setLocalization($localizedName->getLocalization());
                $product->addSlugPrototype($localizedSlug);
            }
        }
    }
}
