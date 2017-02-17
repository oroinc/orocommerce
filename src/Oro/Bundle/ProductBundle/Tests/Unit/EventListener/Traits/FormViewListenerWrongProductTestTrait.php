<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Traits;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @property \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator
 * @property \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper $doctrineHelper
 * @property \PHPUnit_Framework_MockObject_MockObject|Request $request
 */
trait FormViewListenerWrongProductTestTrait
{
    public function testOnProductViewInvalidId()
    {
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onProductView($event);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->listener->onProductView($event);
    }

    public function testOnProductViewEmptyProduct()
    {
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->listener->onProductView($event);
    }
}
