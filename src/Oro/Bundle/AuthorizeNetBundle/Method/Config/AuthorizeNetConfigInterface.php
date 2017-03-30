<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface AuthorizeNetConfigInterface extends PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getApiLogin();

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
}
