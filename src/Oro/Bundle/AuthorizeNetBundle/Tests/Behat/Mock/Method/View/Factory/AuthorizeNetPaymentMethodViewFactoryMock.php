<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\Method\View\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Factory\AuthorizeNetPaymentMethodViewFactory;
use Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\Method\View\AuthorizeNetPaymentMethodViewMock;

class AuthorizeNetPaymentMethodViewFactoryMock extends AuthorizeNetPaymentMethodViewFactory
{
    public function create(AuthorizeNetConfigInterface $config)
    {
        return new AuthorizeNetPaymentMethodViewMock($this->formFactory, $config);
    }
}
