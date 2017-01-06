<?php

namespace Oro\Bundle\PayPalBundle\Bundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;

class LoadPayPalCreditCardTypesData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            PayPalSettings::CARD_VISA => 'Visa',
            PayPalSettings::CARD_MASTERCARD => 'MasterCard',
            PayPalSettings::CARD_DISCOVER => 'Discover',
            PayPalSettings::CARD_AMERICAN_EXPRESS => 'American Express'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'pp_credit_card_types';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return PayPalSettings::CARD_VISA;
    }
}
