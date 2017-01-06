<?php

namespace Oro\Bundle\PayPalBundle\Bundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class LoadPayPalCreditCardPaymentActionData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            PaymentMethodInterface::AUTHORIZE => 'Authorize',
            PaymentMethodInterface::CHARGE => 'Charge'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'pp_credit_card_payment_action';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return PaymentMethodInterface::AUTHORIZE;
    }
}
