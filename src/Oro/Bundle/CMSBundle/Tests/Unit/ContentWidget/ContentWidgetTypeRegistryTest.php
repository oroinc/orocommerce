<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;

class ContentWidgetTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetType */
    private $widgetType;

    /** @var ContentWidgetTypeRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->widgetType = new ContentWidgetTypeStub();

        $this->registry = new ContentWidgetTypeRegistry($this->getIteratorAggregate([$this->widgetType]));
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

    private function getIteratorAggregate(array $data): \IteratorAggregate
    {
        return new class($data) implements \IteratorAggregate {
            /** @var array */
            private $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            /**
             * {@inheritdoc}
             */
            public function getIterator()
            {
                return new \ArrayIterator($this->data);
            }
        };
    }
}
