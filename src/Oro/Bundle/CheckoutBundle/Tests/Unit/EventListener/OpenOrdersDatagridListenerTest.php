<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\OpenOrdersDatagridListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OpenOrdersDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var OpenOrdersDatagridListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new OpenOrdersDatagridListener($this->configManager);
    }

    public function testOnPreBuild()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.frontend_show_open_orders')
            ->willReturn(false);
        $event = new PreBuild($this->createMock(DatagridConfiguration::class), $this->createMock(ParameterBag::class));
        $this->listener->onPreBuild($event);
    }
}
