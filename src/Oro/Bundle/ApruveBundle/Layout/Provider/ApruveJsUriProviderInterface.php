<?php

namespace Oro\Bundle\ApruveBundle\Layout\Provider;

interface ApruveJsUriProviderInterface
{
    /**
     * @param string $paymentMethodIdentifier
     *
     * @return string|null
     */
    public function getUri($paymentMethodIdentifier);
}
