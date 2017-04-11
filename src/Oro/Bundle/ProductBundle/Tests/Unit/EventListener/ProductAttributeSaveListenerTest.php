<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\BeforeEntityAttributeSaveEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductAttributeSaveListener;

use Oro\Component\Testing\Unit\EntityTrait;

class ProductAttributeSaveListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductAttributeSaveListener
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new ProductAttributeSaveListener();
    }

    public function testOnBeforeSaveProduct()
    {
        /** @var BeforeEntityAttributeSaveEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BeforeEntityAttributeSaveEvent::class)
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getAlias')
            ->willReturn('product');
        $event->expects($this->once())
            ->method('getOptions')
            ->willReturn(['datagrid' => ['is_attribute' => true ]]);
        $event->expects($this->once())
            ->method('setOptions')
            ->willReturn([
                'attribute' =>  [
                    'is_attribute' => true
                ],
                'datagrid'  =>  [
                    'is_visible'   =>  ProductAttributeSaveListener::YES_AND_DO_NOT_DISPLAY_OPTION_ID
                ]
            ]);

        $this->listener->onBeforeSave($event);
    }

    public function testOnBeforeSaveNonProduct()
    {
        /** @var BeforeEntityAttributeSaveEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BeforeEntityAttributeSaveEvent::class)
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getAlias')
            ->willReturn('contact');
        $event->expects($this->never())
            ->method('getOptions');
        $event->expects($this->never())
            ->method('setOptions');

        $this->listener->onBeforeSave($event);
    }
}
