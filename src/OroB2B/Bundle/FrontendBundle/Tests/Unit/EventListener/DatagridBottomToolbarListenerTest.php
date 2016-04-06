<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\FrontendBundle\EventListener\DatagridBottomToolbarListener;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class DatagridBottomToolbarListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridBottomToolbarListener
     */
    protected $listener;

    /**
     * @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridConfig;

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    public function setUp()
    {
        $this->frontendHelper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()->getMock();
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()->getMock();
        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()->getMock();
        $this->event->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->datagridConfig);
        $this->listener = new DatagridBottomToolbarListener($this->frontendHelper);
    }

    public function testIsNotApplicableNotFrontendRequest()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->datagridConfig->expects($this->never())
            ->method('offsetSetByPath');
        $this->listener->onBuildBefore($this->event);
    }

    public function testIsNotApplicableAlreadySet()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->datagridConfig->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][toolbarOptions][placement][bottom]')
            ->willReturn(false);
        $this->datagridConfig->expects($this->never())
            ->method('offsetSetByPath');
        $this->listener->onBuildBefore($this->event);
    }

    public function testOnBuildBefore()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->datagridConfig->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[options][toolbarOptions][placement][bottom]')
            ->willReturn(null);
        $this->datagridConfig->expects($this->once())
            ->method('offsetSetByPath')
            ->with('[options][toolbarOptions][placement][bottom]', true)
            ->willReturn(null);
        $this->listener->onBuildBefore($this->event);
    }
}
