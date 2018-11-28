<?php

namespace Oro\Bundle\MoneyOrderBundle\EventListener;

use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Event\CollectFormattedPaymentOptionsEvent;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Listener collects formatted payment options for money/order payment method
 */
class MoneyOrderOptionsListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param CollectFormattedPaymentOptionsEvent $event
     */
    public function onCollectPaymentOptions(CollectFormattedPaymentOptionsEvent $event)
    {
        $paymentMethodView = $event->getPaymentMethodView();
        if (!$paymentMethodView instanceof MoneyOrderView) {
            return;
        }

        // Pass empty context according to interface
        $options = $paymentMethodView->getOptions(new PaymentContext([]));
        $event->addOption(sprintf('%s: %s', $this->translator->trans('oro.money_order.pay_to'), $options['pay_to']));
        $event->addOption(sprintf('%s: %s', $this->translator->trans('oro.money_order.send_to'), $options['send_to']));
    }
}
