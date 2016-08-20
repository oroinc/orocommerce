<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FrontendBundle\EventListener\DatagridBottomToolbarListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class DatagridBottomToolbarListenerTest extends FrontendDatagridListenerTestCase
{
    /**
     * @var DatagridBottomToolbarListener
     */
    protected $listener;

    /**
     * @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    public function setUp()
    {
        parent::setUp();
        $this->event = $this->getBuildBeforeEventMock($this->datagridConfig);
    }

    /**
     * {@inheritDoc}
     */
    public function createListener(FrontendHelper $helper)
    {
        return new DatagridBottomToolbarListener($helper);
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
