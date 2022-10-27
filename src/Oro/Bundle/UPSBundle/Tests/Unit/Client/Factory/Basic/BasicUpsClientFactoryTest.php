<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Client\Factory\Basic;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

class BasicUpsClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RestClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $restClientFactoryMock;

    /**
     * @var UpsClientUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $upsClientUrlProviderMock;

    /**
     * @var BasicUpsClientFactory
     */
    private $testedUpsClientFactory;

    protected function setUp(): void
    {
        $this->restClientFactoryMock = $this->createMock(RestClientFactoryInterface::class);
        $this->upsClientUrlProviderMock = $this->createMock(UpsClientUrlProviderInterface::class);

        $this->testedUpsClientFactory = new BasicUpsClientFactory(
            $this->restClientFactoryMock,
            $this->upsClientUrlProviderMock
        );
    }

    public function testCreateUpsClient()
    {
        $isTestMode = true;
        $url = 'some_url';
        $expectedRestClient = $this->createMock(RestClientInterface::class);

        $this->upsClientUrlProviderMock
            ->expects($this->once())
            ->method('getUpsUrl')
            ->with($isTestMode)
            ->willReturn($url);

        $this->restClientFactoryMock
            ->expects($this->once())
            ->method('createRestClient')
            ->with($url, [])
            ->willReturn($expectedRestClient);

        $actualResult = $this->testedUpsClientFactory->createUpsClient($isTestMode);

        $this->assertEquals($actualResult, $expectedRestClient);
    }
}
