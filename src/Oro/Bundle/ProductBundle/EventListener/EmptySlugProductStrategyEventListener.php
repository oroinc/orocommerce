<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

/**
 * On product import checks if the slug is empty and generates one from the product name
 */
class EmptySlugProductStrategyEventListener
{
    /** @var SlugGenerator */
    private $slugGenerator;

    public function __construct(SlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @deprecated Use SlugifyEntityHelper to handle slugs.
     */
    public function onProcessAfter(ProductStrategyEvent $event): void
    {
    }
}
