<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;

class ContentWidgetTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    private ContentWidgetTypeInterface $widgetType;
    private ContentWidgetTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->widgetType = new ContentWidgetTypeStub();

        $this->registry = new ContentWidgetTypeRegistry([$this->widgetType]);
    }

    public function testGetWidgetType(): void
    {
        $this->assertSame($this->widgetType, $this->registry->getWidgetType(ContentWidgetTypeStub::getName()));
        $this->assertNull($this->registry->getWidgetType('unknown'));
    }

    public function testGetTypes(): void
    {
        $this->assertEquals([$this->widgetType], $this->registry->getTypes());
    }
}
