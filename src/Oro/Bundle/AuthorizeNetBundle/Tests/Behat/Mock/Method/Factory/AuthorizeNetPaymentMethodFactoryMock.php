<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\Method\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Factory\AuthorizeNetPaymentMethodFactory;
use Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\Method\AuthorizeNetPaymentMethodMock;

class AuthorizeNetPaymentMethodFactoryMock extends AuthorizeNetPaymentMethodFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(AuthorizeNetConfigInterface $config)
    {
        $method = new AuthorizeNetPaymentMethodMock($this->gateway, $config, $this->requestStack);

        if ($this->logger) {
            $method->setLogger($this->logger);
        }

        return $method;
    }
}
