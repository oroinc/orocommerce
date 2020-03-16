<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Event;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\EventDispatcher\Event;

/**
 * Holds the category object and import data. Fires on "after process entity" step in the import/export strategy.
 */
class CategoryStrategyAfterProcessEntityEvent extends Event
{
    /** @var Category */
    protected $category;

    /** @var array */
    protected $rawData;

    /**
     * @param Category $category
     * @param array $rawData
     */
    public function __construct(Category $category, array $rawData)
    {
        $this->category = $category;
        $this->rawData = $rawData;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return array
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }
}
