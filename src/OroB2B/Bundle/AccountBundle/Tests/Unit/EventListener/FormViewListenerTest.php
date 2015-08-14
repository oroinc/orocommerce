<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\AccountBundle\EventListener\FormViewListener;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
     * {@inheritdoc}
     */
    public function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->doctrineHelper = $this->getDoctrineHelper();

        $listener = new FormViewListener($translator, $this->doctrineHelper);
        $this->listener = $listener;
    }

    public function testOnCategoryEditNoRequest()
    {
        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->never())
            ->method('getScrollData');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $this->listener->setRequest(null);
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
            ->with('OroB2BCatalogBundle:Category')
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

        $this->listener->setRequest($this->getRequest());
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
