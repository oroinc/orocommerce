<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Helper;

use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\ShippingBundle\Tools\FilteredDatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class FilteredDatagridRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridRouteHelper;

    /**
     * @var string $gridRouteName
     */
    protected $gridRouteName;

    /**
     * @var string $gridName
     */
    protected $gridName;

    /**
     * @var FilteredDatagridRouteHelper
     */
    protected $helper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);

        $this->gridRouteName = 'route_name';
        $this->gridName = 'grid_name';

        $this->helper = new FilteredDatagridRouteHelper(
            $this->gridRouteName,
            $this->gridName,
            $this->datagridRouteHelper
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->datagridRouteHelper, $this->helper);
    }

    public function testGenerate()
    {
        $this->datagridRouteHelper->expects($this->once())->method('generate')->with(
            $this->gridRouteName,
            $this->gridName,
            ['f' => ['filterName' => ['value' => ['' => '10']]]],
            RouterInterface::ABSOLUTE_PATH
        )->willReturn('generatedURL');

        $this->assertEquals('generatedURL', $this->helper->generate(['filterName' => 10]));
    }
}
