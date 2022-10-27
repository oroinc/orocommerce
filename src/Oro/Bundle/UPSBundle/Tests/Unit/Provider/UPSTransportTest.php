<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class UPSTransportTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PRODUCTION_URL = 'prod.example.org';
    private const TEST_URL = 'test.example.org';

    /** @var RestClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var UpsClientUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $upsClientUrlProviderMock;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var UPSTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->upsClientUrlProviderMock = $this->createMock(UpsClientUrlProviderInterface::class);

        $this->transport = new UPSTransport($this->upsClientUrlProviderMock, $this->logger);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetLabel()
    {
        self::assertEquals('oro.ups.transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        self::assertEquals(UPSTransportSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        self::assertEquals(UPSSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetPrices()
    {
        $rateRequest = $this->createMock(PriceRequest::class);

        $integration = new Channel();
        $transportEntity = new UPSSettings();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock(RestResponseInterface::class);

        $json = '{
                   "RateResponse":{
                      "RatedShipment":{
                         "Service": {
                            "Code":"02"
                         },
                         "TotalCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                         }
                      }
                   }
                }';

        $restResponse->expects(self::once())
            ->method('json')
            ->willReturn($json);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($restResponse);

        $this->transport->getPriceResponse($rateRequest, $transportEntity);
    }

    public function testGetPricesException()
    {
        $rateRequest = $this->createMock(PriceRequest::class);

        $integration = new Channel();
        $transportEntity = $this->getEntity(UPSSettings::class, ['id' => '123']);
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock(RestResponseInterface::class);

        $json = '{
            "Fault":{
                "faultcode":"Client", 
                "faultstring":"An exception has been raised as a result of client data.", 
                "detail":{
                    "Errors":{
                        "ErrorDetail":{
                            "Severity":"Hard", 
                            "PrimaryErrorCode":{
                                "Code":"111100", 
                                "Description":"The requested service is invalid from the selected origin."
                            }
                        }
                    }
                }
            }
        }';

        $jsonArr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $restResponse->expects(self::once())
            ->method('json')
            ->willReturn($jsonArr);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($restResponse);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'Price request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    json_encode($jsonArr['Fault'], JSON_THROW_ON_ERROR)
                )
            );

        $this->transport->getPriceResponse($rateRequest, $transportEntity);
    }

    /**
     * @dataProvider clientBaseOptionDataProvider
     */
    public function testClientBaseUrl(bool $testMode, string $expectedUrl)
    {
        $transportEntity = $this->getEntity(
            UPSSettings::class,
            [
                'id' => '123',
                'upsTestMode' => $testMode,
            ]
        );

        $integration = new Channel();
        $integration->setTransport($transportEntity);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($this->createMock(RestResponseInterface::class));

        $this->upsClientUrlProviderMock->expects($this->once())
            ->method('getUpsUrl')
            ->with($testMode)
            ->willReturn($expectedUrl);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->with($expectedUrl)
            ->willReturn($this->client);

        $this->transport->getPriceResponse($this->createMock(PriceRequest::class), $transportEntity);
    }

    public function clientBaseOptionDataProvider(): array
    {
        return [
            ['testMode' => false, 'expectedUrl' => self::PRODUCTION_URL],
            ['testMode' => true, 'expectedUrl' => self::TEST_URL],
        ];
    }
}
