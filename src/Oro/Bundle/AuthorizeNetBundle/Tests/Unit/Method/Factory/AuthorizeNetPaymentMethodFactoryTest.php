<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Factory\AuthorizeNetPaymentMethodFactory;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Psr\Log\LoggerInterface;

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

    /**
     * @var AuthorizeNetPaymentMethodFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->factory = new AuthorizeNetPaymentMethodFactory($this->gateway);
        $this->factory->setLogger($this->logger);
    }

    public function testCreate()
    {
        /** @var AuthorizeNetConfigInterface; $config */
        $config = $this->createMock(AuthorizeNetConfigInterface::class);

        $method = new AuthorizeNetPaymentMethod($this->gateway, $config);
        $method->setLogger($this->logger);

        $this->assertEquals($method, $this->factory->create($config));
    }
}
