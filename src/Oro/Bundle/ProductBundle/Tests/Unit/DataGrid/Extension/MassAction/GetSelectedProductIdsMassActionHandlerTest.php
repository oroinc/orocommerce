<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\MassAction;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction\GetSelectedProductIdsMassActionHandler;

class GetSelectedProductIdsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var GetSelectedProductIdsMassActionHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->handler = new GetSelectedProductIdsMassActionHandler($this->configManager);
    }

    public function testHandleWhenExceedLimitationAndNoForceParameter()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn(100);
        /** @var MassActionInterface|\PHPUnit_Framework_MockObject_MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit_Framework_MockObject_MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);
        $iterableResult->expects($this->once())
            ->method('count')
            ->willReturn(200);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => false]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(
            false,
            GetSelectedProductIdsMassActionHandler::FAILED_RESPONSE_MESSAGE
        );
        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenExceedLimitationAndHaveForceParameter()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn(100);
        /** @var MassActionInterface|\PHPUnit_Framework_MockObject_MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit_Framework_MockObject_MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => true]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(
            true,
            GetSelectedProductIdsMassActionHandler::SUCCESS_RESPONSE_MESSAGE,
            ['ids' => []]
        );
        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenDoesNotExceedLimitation()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn(100);
        /** @var MassActionInterface|\PHPUnit_Framework_MockObject_MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit_Framework_MockObject_MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);
        $iterableResult->expects($this->once())
            ->method('count')
            ->willReturn(100);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => false]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(
            true,
            GetSelectedProductIdsMassActionHandler::SUCCESS_RESPONSE_MESSAGE,
            ['ids' => []]
        );
        $this->assertEquals($expectedResponse, $response);
    }
}
