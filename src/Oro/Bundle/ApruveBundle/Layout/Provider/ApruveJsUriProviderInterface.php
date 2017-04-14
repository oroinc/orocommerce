<?php

namespace Oro\Bundle\ApruveBundle\Layout\Provider;

interface ApruveJsUriProviderInterface
{
    /**
     * @param string $paymentMethodIdentifier
     *
     * @return string
     */
    public function getUri($paymentMethodIdentifier);

    /**
     * @param $paymentMethodIdentifier
     *
     * @return bool
     */
    public function isSupported($paymentMethodIdentifier);
}
