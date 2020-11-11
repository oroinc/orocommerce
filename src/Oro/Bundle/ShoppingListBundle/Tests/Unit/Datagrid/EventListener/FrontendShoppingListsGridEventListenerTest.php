<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendShoppingListsGridEventListener;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;

class FrontendShoppingListsGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListTotalManager;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userCurrencyManager;

    /** @var FrontendShoppingListsGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->listener = new FrontendShoppingListsGridEventListener(
            $this->currentShoppingListManager,
            $this->shoppingListTotalManager,
            $this->userCurrencyManager
        );
    }

    public function testOnBuildBefore(): void
    {
        $params = new ParameterBag();

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $config = DatagridConfiguration::create([]);

        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(42);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getShoppingLists')
            ->willReturn([$shoppingList]);

        $this->shoppingListTotalManager->expects($this->once())
            ->method('setSubtotals')
            ->with([$shoppingList], true);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        $this->assertEquals(
            ['current_currency' => 'USD', 'default_shopping_list_id' => $shoppingList->getId()],
            $params->all()
        );
        $this->assertEquals([], $config->toArray());
    }

    public function testOnBuildBeforeWithoutShoppingLists(): void
    {
        $params = new ParameterBag();

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getShoppingLists')
            ->willReturn([]);

        $this->shoppingListTotalManager->expects($this->never())
            ->method('setSubtotals');

        $config = DatagridConfiguration::create([]);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn(null);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        $this->assertEquals(['current_currency' => 'USD'], $params->all());
        $this->assertEquals([], $config->toArray());
    }

    public function testOnBuildBeforeWithoutCurrent(): void
    {
        $params = new ParameterBag();

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $config = DatagridConfiguration::create([]);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn(null);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        $this->assertEquals(['current_currency' => 'USD'], $params->all());
        $this->assertEquals([], $config->toArray());
    }
}
