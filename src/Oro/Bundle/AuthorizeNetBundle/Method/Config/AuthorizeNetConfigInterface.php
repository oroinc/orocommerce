<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface AuthorizeNetConfigInterface extends PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getApiLoginId();

    /**
     * @return string
     */
    public function getTransactionKey();

    /**
     * @return string
     */
    public function getClientKey();

    /**
     * @return bool
     */
    public function isTestMode();

    /**
     * @return array
     */
    public function getAllowedCreditCards();

    /**
     * @return string
     */
    public function getPurchaseAction();

    /**
     * @return bool
     */
    public function isRequireCvvEntryEnabled();
}
