<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Event;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = ['test'];
        $modifiedData = ['test1'];
        $context = $this->createMock(ContextInterface::class);

        $event = new ProductDataConverterEvent($data);

        $this->assertSame($data, $event->getData());

        $event->setData($modifiedData);

        $this->assertSame($modifiedData, $event->getData());
        $this->assertNotSame($data, $event->getData());

        $event->setContext($context);

        $this->assertSame($context, $event->getContext());
    }
}
