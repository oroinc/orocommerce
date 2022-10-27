<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\MoneyOrderBundle\EventListener\MoneyOrderOptionsListener;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use Oro\Bundle\PaymentBundle\Event\CollectFormattedPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MoneyOrderOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var MoneyOrderOptionsListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listener = new MoneyOrderOptionsListener($this->translator);
    }

    public function testOnCollectPaymentOptionsWhenNoMoneyOrderView()
    {
        /** @var PaymentMethodViewInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethodView */
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $event = new CollectFormattedPaymentOptionsEvent($paymentMethodView);
        $paymentMethodView->expects($this->never())
            ->method('getOptions');

        $this->listener->onCollectPaymentOptions($event);
    }

    public function testOnCollectPaymentOptions()
    {
        $payTo = 'payTo option';
        $sendTo = 'sendTo option';
        $paymentMethodConfig = new MoneyOrderConfig([
            MoneyOrderConfig::PAY_TO_KEY => $payTo,
            MoneyOrderConfig::SEND_TO_KEY => $sendTo,
        ]);
        $paymentMethodView = new MoneyOrderView($paymentMethodConfig);
        $event = new CollectFormattedPaymentOptionsEvent($paymentMethodView);

        $payToTransLabel = 'payTo trans label';
        $sendToTransLabel = 'sendTo trans label';
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['oro.money_order.pay_to'],
                ['oro.money_order.send_to']
            )
            ->willReturnOnConsecutiveCalls($payToTransLabel, $sendToTransLabel);

        $this->listener->onCollectPaymentOptions($event);
        $expectedOptions = [
            $payToTransLabel.': '.$payTo,
            $sendToTransLabel.': '.$sendTo,
        ];
        self::assertEquals($expectedOptions, $event->getOptions());
    }
}
