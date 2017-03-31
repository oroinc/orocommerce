<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;

interface OptionInterface
{
    /**
     * @return string
     */
    public function getTransactionType();

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @return string
     */
    public function getDataDescriptor();

    /**
     * @return string
     */
    public function getDataValue();


    /**
     * @param AuthorizeNetConfigInterface $config
     */
    public function setConfig(AuthorizeNetConfigInterface $config);

    /**
     * @return AuthorizeNetConfigInterface
     */
    public function getConfig();
}
