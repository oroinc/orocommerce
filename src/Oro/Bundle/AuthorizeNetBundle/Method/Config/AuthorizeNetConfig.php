<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

class AuthorizeNetConfig extends AbstractParameterBagPaymentConfig implements AuthorizeNetConfigInterface
{
    const LABEL_KEY = 'label';
    const SHORT_LABEL_KEY = 'short_label';
    const ADMIN_LABEL_KEY = 'admin_label';
    const PAYMENT_METHOD_IDENTIFIER_KEY = 'payment_method_identifier';
    const ALLOWED_CREDIT_CARD_TYPES_KEY = 'allowed_credit_card_types';
    const PURCHASE_ACTION_KEY  = 'purchase_action';
    const TEST_MODE_KEY  = 'test_mode';
    const API_LOGIN_ID = 'api_login_id';
    const TRANSACTION_KEY = 'transaction_key';
    const CLIENT_KEY = 'client_key';
    const REQUIRE_CVV_ENTRY_KEY = 'require_cvv_entry';

    /**
     * {@inheritdoc}
     */
    public function isTestMode()
    {
        return (bool)$this->get(self::TEST_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCreditCards()
    {
        return (array)$this->get(self::ALLOWED_CREDIT_CARD_TYPES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseAction()
    {
        return (string)$this->get(self::PURCHASE_ACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiLoginId()
    {
        return (string)$this->get(self::API_LOGIN_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionKey()
    {
        return (string)$this->get(self::TRANSACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getClientKey()
    {
        return (string)$this->get(self::CLIENT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isRequireCvvEntryEnabled()
    {
        return (bool)$this->get(self::REQUIRE_CVV_ENTRY_KEY);
    }
}
