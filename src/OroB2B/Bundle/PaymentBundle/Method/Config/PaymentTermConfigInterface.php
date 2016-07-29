<?php

namespace OroB2B\Bundle\PaymentBundle\Method\Config;

interface PaymentTermConfigInterface extends
    PaymentConfigInterface,
    CountryConfigAwareInterface,
    CurrencyConfigAwareInterface
{
}
