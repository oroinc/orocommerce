<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\VisibilityBundle\Form\EventListener\FormViewListener;
use Oro\Bundle\CatalogBundle\Entity\Category;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->doctrineHelper = $this->getDoctrineHelper();

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = new FormViewListener($translator, $this->doctrineHelper, $this->requestStack);
        $this->listener = $listener;
    }

    public function testOnCategoryEditNoRequest()
    {
        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->never())
            ->method('getScrollData');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->listener->onCategoryEdit($event);
    }

    public function testOnCategoryEdit()
    {
        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->getScrollData());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCatalogBundle:Category')
            ->willReturn(new Category());

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->willReturn('');
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->getRequest());
        $this->listener->onCategoryEdit($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        $helper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        return $helper;
    }

    /**
     * @return BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBeforeListRenderEvent()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        return $event;
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
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        return $request;
    }
}
