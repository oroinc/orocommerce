<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Event;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Holds the category object and import data. Fires on "after process entity" step in the import/export strategy.
 */
class CategoryStrategyAfterProcessEntityEvent extends Event
{
    /** @var Category */
    protected $category;

    /** @var array */
    protected $rawData;

    public function __construct(Category $category, array $rawData)
    {
        $this->category = $category;
        $this->rawData = $rawData;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }
}
