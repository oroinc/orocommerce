<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Factory\ApruveRestClientFactory;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Psr\Log\LoggerInterface;

class ApruveRestClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfig;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var RestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restClientFactory;

    /**
     * @var ApruveRestClientFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->restClientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->apruveConfig = $this->createMock(ApruveConfigInterface::class);
        $this->factory = new ApruveRestClientFactory($this->restClientFactory, $this->logger);
    }

    public function testCreate()
    {
        $expected = new ApruveRestClient($this->apruveConfig, $this->restClientFactory, $this->logger);
        $actual = $this->factory->create($this->apruveConfig);

        static::assertEquals($expected, $actual);
    }
}
