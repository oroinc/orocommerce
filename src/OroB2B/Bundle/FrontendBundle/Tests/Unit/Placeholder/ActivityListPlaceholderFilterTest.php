<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

use OroB2B\Bundle\FrontendBundle\Placeholder\ActivityListPlaceholderFilter;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class ActivityListPlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PlaceholderFilter
     */
    protected $basicFilter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var ActivityListPlaceholderFilter
     */
    protected $filter;

    protected function setUp()
    {
        $this->basicFilter = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new ActivityListPlaceholderFilter($this->basicFilter, $this->helper, $this->requestStack);
    }

    public function testIsApplicableNoRequest()
    {
        $entity = new \stdClass();
        $pageType = 'view';

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->will($this->returnValue(null));

        $this->basicFilter->expects($this->once())
            ->method('isApplicable')
            ->with($entity, $pageType)
            ->will($this->returnValue(true));

        $this->assertTrue($this->filter->isApplicable($entity, $pageType));
    }

    public function testIsApplicableNotFrontend()
    {
        $entity = new \stdClass();
        $pageType = 1;

        $this->assertIsFrontendRouteCall(false);

        $this->basicFilter->expects($this->once())
            ->method('isApplicable')
            ->with($entity, $pageType)
            ->will($this->returnValue(true));

        $this->assertTrue($this->filter->isApplicable($entity, $pageType));
    }

    public function testIsApplicable()
    {
        $entity = new \stdClass();
        $pageType = 1;

        $this->assertIsFrontendRouteCall(true);

        $this->basicFilter->expects($this->never())
            ->method('isApplicable');

        $this->assertFalse($this->filter->isApplicable($entity, $pageType));
    }

    public function testIsAllowedButtonNotFrontend()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeGroupingChainWidgetEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertIsFrontendRouteCall(false);

        $this->basicFilter->expects($this->once())
            ->method('isAllowedButton')
            ->with($event);

        $this->filter->isAllowedButton($event);
    }

    public function testIsAllowedButton()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeGroupingChainWidgetEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertIsFrontendRouteCall(true);

        $event->expects($this->once())
            ->method('setWidgets')
            ->with([]);
        $event->expects($this->once())
            ->method('stopPropagation');

        $this->basicFilter->expects($this->never())
            ->method('isAllowedButton');

        $this->filter->isAllowedButton($event);
    }

    /**
     * @param bool $isFrontend
     */
    protected function assertIsFrontendRouteCall($isFrontend)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->will($this->returnValue($request));

        $this->helper->expects($this->once())
            ->method('isFrontendRequest')
            ->with($request)
            ->will($this->returnValue($isFrontend));
    }
}
