<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class FormViewListenerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->doctrine, $this->listener);
    }

    /**
     * @return BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBeforeListRenderEvent()
    {
        $event = $this->getBeforeListRenderEventMock();

        $event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->getScrollData());

        return $event;
    }

    /**
     * @return BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBeforeListRenderEventMock()
    {
        return $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ScrollData
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->getMock('Oro\Bundle\UIBundle\View\ScrollData');

        $scrollData->expects($this->once())
            ->method('addBlock');

        $scrollData->expects($this->once())
            ->method('addSubBlock');

        $scrollData->expects($this->once())
            ->method('addSubBlockData');

        return $scrollData;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected function getRequest()
    {
        return $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
