<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDTransportEntity;
use Oro\Bundle\DPDBundle\Form\Type\DPDTransportSettingsType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

class DPDTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $symmetricCrypter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var DPDTransport
     */
    protected $transport;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    protected function setUp()
    {
        $this->client = $this->createMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface');

        $this->clientFactory = $this->createMock(
            'Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface'
        );

        $this->symmetricCrypter = $this->createMock(
            'Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface'
        );

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->transport = new DPDTransport($this->logger, $this->symmetricCrypter);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetLabel()
    {
        static::assertEquals('oro.dpd.transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        static::assertEquals(DPDTransportSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertEquals('Oro\Bundle\DPDBundle\Entity\DPDTransport', $this->transport->getSettingsEntityFQCN());
    }

    public function testGetSetOrderResponse()
    {
        $setOrderRequest = $this->createMock(SetOrderRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface');

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "LabelResponse": {
                      "LabelPDF": "base 64 encoded pdf",
                      "LabelDataList": {
                         "LabelData": {
                            "YourInternalID": "an ID",
                            "ParcelNo": "parcel number"
                         }
                      }
                   }
                }';
        $jsonArr = json_decode($json, true);

        $restResponse->expects(static::once())
            ->method('json')
            ->willReturn($jsonArr);

        $this->client->expects(static::once())
            ->method('post')
            ->willReturn($restResponse);

        $setOrderResponse = $this->transport->getSetOrderResponse($setOrderRequest, $transportEntity);
        static::assertInstanceOf('Oro\Bundle\DPDBundle\Model\SetOrderResponse', $setOrderResponse);

    }


    public function testGetSetOrderResponseRestException()
    {
        $setOrderRequest = $this->createMock(SetOrderRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $this->client->expects(static::once())
            ->method('post')
            ->will($this->throwException(new RestException('404')));

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'setOrder REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    '404'
                )
            );
        $this->transport->getSetOrderResponse($setOrderRequest, $transportEntity);
    }

    public function testGetZipCodeRulesResponse()
    {
        $zipCodeRulesRequest = $this->createMock(ZipCodeRulesRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface');

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "ZipCodeRules": {
                      "Country": "a country",
                      "ZipCode": "zip code",
                      "NoPickupDays": "01.01.2017,18.04.2017,25.12.2017",
                      "ExpressCutOff": "12:00",
                      "ClassicCutOff": "08:00",
                      "PickupDepot": "0197",
                      "State": "a state"
                   }
                }';
        $jsonArr = json_decode($json, true);

        $restResponse->expects(static::once())
            ->method('json')
            ->willReturn($jsonArr);

        $this->client->expects(static::once())
            ->method('get')
            ->willReturn($restResponse);

        $zipCodeRulesResponse = $this->transport->getZipCodeRulesResponse($zipCodeRulesRequest, $transportEntity);
        static::assertInstanceOf('Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse', $zipCodeRulesResponse);

    }


    public function testGetZipCodeRulesResponseRestException()
    {
        $getZipCodeRulesRequest = $this->createMock(ZipCodeRulesRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $this->client->expects(static::once())
            ->method('get')
            ->will($this->throwException(new RestException('404')));

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'ZipCodeRules REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    '404'
                )
            );
        $this->transport->getZipCodeRulesResponse($getZipCodeRulesRequest, $transportEntity);
    }
}
