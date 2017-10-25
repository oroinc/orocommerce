<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Stub;

use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedConfigProvider;

class PreviouslyPurchasedConfigProviderStub extends PreviouslyPurchasedConfigProvider
{
    const PREVIOUSLY_PURCHASED_DATE_STRING_WITH_UTC_LOCALE = '2017-08-20 00:00:00';
    const PREVIOUSLY_PURCHASED_DATE_STRING_WITH_BERLIN_LOCALE = '2017-08-19 22:00:00';

    /**
     * @param \DateTimeZone $dateTimeZone
     *
     * @return \DateTime
     */
    protected function getDateTimeInCurrentLocale(\DateTimeZone $dateTimeZone)
    {
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            '2017-08-21 00:00:00',
            $dateTimeZone
        );
    }
}
