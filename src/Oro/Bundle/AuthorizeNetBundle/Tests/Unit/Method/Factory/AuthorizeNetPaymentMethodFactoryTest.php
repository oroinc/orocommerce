<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\Factory;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Factory\AuthorizeNetPaymentMethodFactory;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;

class AuthorizeNetPaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var LoggerInterface;
     */
    protected $logger;

    /** @var  RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /**
     * @var AuthorizeNetPaymentMethodFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->factory = new AuthorizeNetPaymentMethodFactory($this->gateway, $this->requestStack);
        $this->factory->setLogger($this->logger);
    }

    public function testCreate()
    {
        /** @var AuthorizeNetConfigInterface; $config */
        $config = $this->createMock(AuthorizeNetConfigInterface::class);

        $method = new AuthorizeNetPaymentMethod($this->gateway, $config, $this->requestStack);
        $method->setLogger($this->logger);

        $this->assertEquals($method, $this->factory->create($config));
    }
}
