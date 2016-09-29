<?php

namespace Oro\Bundle\InfinitePayBundle\Configuration;

interface InfinitePayConfigInterface
{
    /**
     * @return bool
     */
    public function getIsActive();

    /** @var string */
    public function getLabel();

    /**
     * @return int
     */
    public function getOrder();

    /**
     * @return string
     */
    public function getShortLabel();

    /**
     * @return bool
     */
    public function getDebugMode();

    /**
     * @return string
     */
    public function getClientRef();

    /**
     * @return string
     */
    public function getUsernameToken();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @return string
     */
    public function getPassword();

    /**
     * @return bool
     */
    public function isAutoCaptureActive();

    /**
     * @return bool
     */
    public function isAutoActivationActive();

    /**
     * @return int
     */
    public function getInvoiceDuePeriod();

    /**
     * @return int
     */
    public function getShippingDuration();
}
