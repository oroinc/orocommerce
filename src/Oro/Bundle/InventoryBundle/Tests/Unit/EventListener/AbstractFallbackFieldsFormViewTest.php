<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

abstract class AbstractFallbackFieldsFormViewTest extends FormViewListenerTestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    abstract protected function callTestMethod();

    /**
     * @return object
     */
    abstract protected function getEntity();

    /**
     * @return array
     */
    abstract protected function getExpectedScrollData();

    protected function setUp()
    {
        parent::setUp();

        $this->requestStack = new RequestStack();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack->push($this->request);

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getBeforeListRenderEventMock();
    }

    protected function tearDown()
    {
        unset(
            $this->event,
            $this->doctrine,
            $this->requestStack,
            $this->request
        );

        parent::tearDown();
    }

    public function testOnCategoryEditIgnoredIfNoId()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->callTestMethod();
    }

    public function testOnCategoryEditIgnoredIfNoFound()
    {
        $entity = $this->getEntity();
        $this->em->expects($this->once())
            ->method('getReference');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($this->em);

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $this->callTestMethod();
    }

    public function testEditRendersAndAddsSubBlock()
    {
        $entity = $this->getEntity();
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn($entity);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($this->em);

        $env = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $scrollData = $this->createMock(ScrollData::class);

        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);

        $env->expects($this->once())
            ->method('render');

        $scrollData->expects($this->once())
            ->method('addSubBlockData');

        $scrollData->expects($this->once())
            ->method('getData')
            ->willReturn($this->getExpectedScrollData());

        $this->callTestMethod();
    }
}
