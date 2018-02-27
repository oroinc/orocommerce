<?php

namespace Oro\Component\Testing\Unit;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class FormViewListenerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->em, $this->translator);
    }

    /**
     * @deprecated Don't mock DTO. Just instantiate it.
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
     * @deprecated Don't mock DTO. Just instantiate it.
     * @return BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBeforeListRenderEventMock()
    {
        return $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @deprecated Don't mock DTO. Just instantiate it.
     * @return \PHPUnit_Framework_MockObject_MockObject|ScrollData
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->createMock(ScrollData::class);

        $scrollData->expects($this->once())
            ->method('addBlock');

        $scrollData->expects($this->any())
            ->method('addSubBlock');

        $scrollData->expects($this->any())
            ->method('addSubBlockData');

        return $scrollData;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected function getRequest()
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
