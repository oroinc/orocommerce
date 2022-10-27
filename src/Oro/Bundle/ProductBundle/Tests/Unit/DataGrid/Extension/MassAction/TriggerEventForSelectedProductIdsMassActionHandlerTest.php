<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\MassAction;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction\TriggerEventForSelectedProductIdsMassActionHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class TriggerEventForSelectedProductIdsMassActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var TriggerEventForSelectedProductIdsMassActionHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->handler = new TriggerEventForSelectedProductIdsMassActionHandler(
            $this->configManager,
            $this->translator
        );
    }

    public function testHandleWhenExceedLimitationAndNoForceParameter()
    {
        $limit = 100;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn($limit);
        /** @var MassActionInterface|\PHPUnit\Framework\MockObject\MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit\Framework\MockObject\MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);
        $iterableResult->expects($this->once())
            ->method('count')
            ->willReturn(200);
        $translatedMessage = 'some translated message';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(TriggerEventForSelectedProductIdsMassActionHandler::FAILED_RESPONSE_MESSAGE, ['%limit%' => $limit])
            ->willReturn($translatedMessage);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => false]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(false, $translatedMessage);
        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenExceedLimitationAndHaveForceParameter()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn(100);
        /** @var MassActionInterface|\PHPUnit\Framework\MockObject\MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit\Framework\MockObject\MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);
        $translatedMessage = 'some translated message';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(TriggerEventForSelectedProductIdsMassActionHandler::SUCCESS_RESPONSE_MESSAGE)
            ->willReturn($translatedMessage);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => true]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(true, $translatedMessage, ['ids' => []]);
        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleWhenDoesNotExceedLimitation()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_collections_mass_action_limitation')
            ->willReturn(100);
        /** @var MassActionInterface|\PHPUnit\Framework\MockObject\MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        /** @var IterableResult|\PHPUnit\Framework\MockObject\MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResult::class);
        $iterableResult->expects($this->once())
            ->method('count')
            ->willReturn(100);
        $translatedMessage = 'some translated message';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(TriggerEventForSelectedProductIdsMassActionHandler::SUCCESS_RESPONSE_MESSAGE)
            ->willReturn($translatedMessage);

        $args = new MassActionHandlerArgs($massAction, $datagrid, $iterableResult, ['force' => false]);
        $response = $this->handler->handle($args);

        $expectedResponse = new MassActionResponse(true, $translatedMessage, ['ids' => []]);
        $this->assertEquals($expectedResponse, $response);
    }
}
