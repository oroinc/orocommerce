<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;

class ContentWidgetTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetWidgetType(): void
    {
        $widgetType = new ContentWidgetTypeStub();

        $registry = new ContentWidgetTypeRegistry(new \ArrayIterator([$widgetType]));

        $this->assertSame($widgetType, $registry->getWidgetType(ContentWidgetTypeStub::getName()));
        $this->assertNull($registry->getWidgetType('unknown'));
    }
}
