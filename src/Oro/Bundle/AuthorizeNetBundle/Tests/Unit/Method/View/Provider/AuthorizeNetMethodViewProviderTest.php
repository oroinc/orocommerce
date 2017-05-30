<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\View\Provider\AbstractMethodViewProviderTest;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider\AuthorizeNetConfigProviderInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Factory\AuthorizeNetPaymentMethodViewFactoryInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Provider\AuthorizeNetMethodViewProvider;

class AuthorizeNetMethodViewProviderTest extends AbstractMethodViewProviderTest
{
    public function setUp()
    {
        $this->factory = $this->createMock(AuthorizeNetPaymentMethodViewFactoryInterface::class);
        $this->configProvider = $this->createMock(AuthorizeNetConfigProviderInterface::class);
        $this->paymentConfigClass = AuthorizeNetConfigInterface::class;
        $this->provider = new AuthorizeNetMethodViewProvider($this->factory, $this->configProvider);
    }
}
