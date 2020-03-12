<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Event;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Event\CategoryStrategyAfterProcessEntityEvent;

class CategoryStrategyAfterProcessEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $category = new Category();
        $rawData = ['test'];

        $event = new CategoryStrategyAfterProcessEntityEvent($category, $rawData);

        $this->assertSame($category, $event->getCategory());
        $this->assertSame($rawData, $event->getRawData());
    }
}
