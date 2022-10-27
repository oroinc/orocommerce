<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\ImportExport\Event\CategoryStrategyAfterProcessEntityEvent;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * On category import checks if the slug is empty and generates one from the category title
 */
class EmptySlugCategoryStrategyEventListener
{
    /** @var SlugGenerator */
    private $slugGenerator;

    public function __construct(SlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    public function onProcessAfter(CategoryStrategyAfterProcessEntityEvent $event): void
    {
        $category = $event->getCategory();

        if ($category->getSlugPrototypes()->isEmpty()) {
            foreach ($category->getTitles() as $localizedTitle) {
                $this->addSlug($category, $localizedTitle);
            }
        }

        if (!$category->getDefaultSlugPrototype() && $category->getDefaultTitle()) {
            $this->addSlug($category, $category->getDefaultTitle());
        }
    }

    private function addSlug(Category $category, CategoryTitle $localizedTitle): void
    {
        $localizedSlug = LocalizedFallbackValue::createFromAbstract($localizedTitle);
        $localizedSlug->setString($this->slugGenerator->slugify($localizedSlug->getString()));

        $category->addSlugPrototype($localizedSlug);
    }
}
