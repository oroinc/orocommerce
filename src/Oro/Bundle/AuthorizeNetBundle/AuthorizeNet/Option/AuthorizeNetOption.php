<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeNetOption extends ParameterBag implements OptionInterface
{
    const TRANSACTION_TYPE = '';
    const AMOUNT = '';
    const AUTHORIZE_DESCRIPTOR = '';
    const DATA_VALUE = '';

    /**
     * @var AuthorizeNetConfigInterface
     */
    protected $config;

    /**
     * @return AuthorizeNetConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param AuthorizeNetConfigInterface $config
     */
    public function setConfig(AuthorizeNetConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionType()
    {
        return (string)$this->get(self::TRANSACTION_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return (float)$this->get(self::AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataDescriptor()
    {
        return (string)$this->get(self::AUTHORIZE_DESCRIPTOR);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataValue()
    {
        return (string)$this->get(self::DATA_VALUE);
    }
}
