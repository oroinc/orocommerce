<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCurrency;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActualizeCurrencyTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CurrencyNameHelper|MockObject $currencyNameHelper;
    private ActualizeCurrency $actualizeCurrency;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currencyNameHelper = $this->createMock(CurrencyNameHelper::class);

        $this->actualizeCurrency = new ActualizeCurrency(
            $this->actionExecutor,
            $this->userCurrencyManager,
            $this->currencyNameHelper
        );
    }

    public function testExecuteWithNoCheckoutCurrency()
    {
        $checkout = $this->createMock(Checkout::class);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $checkout->expects($this->once())
            ->method('getCurrency')
            ->willReturn(null);

        $this->userCurrencyManager->expects($this->never())
            ->method('saveSelectedCurrency');

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->actualizeCurrency->execute($checkout);
    }

    public function testExecuteWithSameCurrency()
    {
        $checkout = $this->createMock(Checkout::class);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $checkout->expects($this->once())
            ->method('getCurrency')
            ->willReturn('USD');

        $this->userCurrencyManager->expects($this->never())
            ->method('saveSelectedCurrency');

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->actualizeCurrency->execute($checkout);
    }

    public function testExecuteWithDifferentCurrency()
    {
        $checkout = $this->createMock(Checkout::class);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $checkout->expects($this->once())
            ->method('getCurrency')
            ->willReturn('EUR');

        $this->userCurrencyManager->expects($this->once())
            ->method('saveSelectedCurrency')
            ->with('EUR');

        $this->currencyNameHelper->expects($this->once())
            ->method('getCurrencyName')
            ->with('EUR', ViewTypeProviderInterface::VIEW_TYPE_NAME)
            ->willReturn('Euro');

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('flash_message', [
                'message' => 'oro.checkout.frontend.checkout.cannot_change_currency',
                'message_parameters' => ['currency' => 'Euro'],
                'type' => 'warning'
            ]);

        $this->actualizeCurrency->execute($checkout);
    }
}
