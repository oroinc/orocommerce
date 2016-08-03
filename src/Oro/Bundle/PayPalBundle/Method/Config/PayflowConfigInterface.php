<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

interface PayflowConfigInterface
{
    /**
     * @return string
     */
    public function getPurchaseAction();

    /**
     * @return bool
     */
    public function isTestMode();

    /**
     * @return array
     */
    public function getCredentials();
}
