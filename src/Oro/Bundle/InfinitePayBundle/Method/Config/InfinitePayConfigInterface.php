<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface InfinitePayConfigInterface extends PaymentConfigInterface
{
    /**
     * @return bool
     */
    public function isTestModeEnabled();

    /**
     * @return bool
     */
    public function isDebugModeEnabled();

    /**
     * @return string
     */
    public function getClientRef();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @return string
     */
    public function getPassword();

    /**
     * @return string
     */
    public function getSecret();

    /**
     * @return bool
     */
    public function isAutoCaptureEnabled();

    /**
     * @return bool
     */
    public function isAutoActivateEnabled();

    /**
     * @return int
     */
    public function getInvoiceDuePeriod();

    /**
     * @return int
     */
    public function getShippingDuration();
}
