<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\ShippingBundle\Tools\FilteredDatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class FilteredDatagridRouteHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridRouteHelper;

    /** @var string */
    private $gridRouteName;

    /** @var string $gridName */
    private $gridName;

    /** @var FilteredDatagridRouteHelper */
    private $helper;

    protected function setUp(): void
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

    public function testGenerate()
    {
        $this->datagridRouteHelper->expects($this->once())
            ->method('generate')
            ->with(
                $this->gridRouteName,
                $this->gridName,
                ['f' => ['filterName' => ['value' => ['' => '10']]]],
                RouterInterface::ABSOLUTE_PATH
            )->willReturn('generatedURL');

        $this->assertEquals('generatedURL', $this->helper->generate(['filterName' => 10]));
    }
}
