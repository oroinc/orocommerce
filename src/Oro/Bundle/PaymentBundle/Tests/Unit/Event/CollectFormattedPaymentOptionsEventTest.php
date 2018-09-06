<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\CollectFormattedPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class CollectFormattedPaymentOptionsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testProperties()
    {
        /** @var PaymentMethodViewInterface $paymentMethodView */
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $event = new CollectFormattedPaymentOptionsEvent($paymentMethodView);
        self::assertSame($paymentMethodView, $event->getPaymentMethodView());
        self::assertCount(0, $event->getOptions());

        $someOption = 'some option';
        $event->addOption($someOption);
        self::assertCount(1, $event->getOptions());
        self::assertEquals([$someOption], $event->getOptions());

        $someNewOption = 'some new option';
        $event->setOptions([$someNewOption]);
        self::assertCount(1, $event->getOptions());
        self::assertEquals([$someNewOption], $event->getOptions());
    }
}
