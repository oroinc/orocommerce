<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

interface PaymentTermConfigInterface extends
    PaymentConfigInterface,
    CountryConfigAwareInterface,
    CurrencyConfigAwareInterface
{
}
