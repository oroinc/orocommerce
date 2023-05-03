<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FedexShippingBundle\Tests\Functional\Stub\SoapClientStub;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ValidateConnectionControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testValidateConnectionActionNoShippingOptions()
    {
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => 0])
        );

        $this->assertResponseHasErrorMessage(
            'No shipping origin options provided.'
            . ' Please, fill them in System Configuration -> Shipping -> Shipping Origin'
        );
    }

    public function testValidateConnectionActionAuthorizationError()
    {
        $this->setConfigShippingOrigin();

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData(SoapClientStub::AUTHORIZATION_ERROR_OPTION)
        );

        $this->assertResponseHasErrorMessage('Authentication error has occurred');
    }

    public function testValidateConnectionActionConnectionError()
    {
        $this->setConfigShippingOrigin();

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData(SoapClientStub::CONNECTION_ERROR_OPTION)
        );

        $this->assertResponseHasErrorMessage('Connection error has occurred. Please, try again later');
    }

    public function testValidateConnectionActionNoServices()
    {
        $this->setConfigShippingOrigin();

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData(SoapClientStub::NO_SERVICES_OPTION)
        );
        $this->assertResponseHasErrorMessage(
            'No services are available for current configuration,'
            . ' make sure that Shipping Origin configuration is correct in'
            . ' System Configuration -> Shipping -> Shipping Origin'
        );
    }

    public function testValidateConnectionActionOk()
    {
        $this->setConfigShippingOrigin();

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData(SoapClientStub::OK_OPTION)
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertTrue($result['success']);
        self::assertEquals('Connection is valid', $result['message']);
    }

    private function getRequestFormData(string $key): array
    {
        return [
            'oro_integration_channel_form' => [
                'type' => 'fedex',
                'name' => 'fedex',
                'transportType' => 'fedex',
                'transport' => [
                    'labels' => [
                        'values' => ['default' => 'fedex'],
                    ],
                    'key' => $key,
                    'password' => 'password',
                    'accountNumber' => 'accountNumber',
                    'meterNumber' => 'meterNumber',
                    'pickupType' => 'pickupType',
                    'unitOfWeight' => 'unitOfWeight',
                    'shippingServices' => ['2'],
                ],
            ],
        ];
    }

    private function setConfigShippingOrigin(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_shipping.shipping_origin',
            (new ShippingOrigin())
                ->setCountry(new Country('US'))
                ->setRegion(new Region('US-CA'))
                ->setCity('City')
                ->setPostalCode('12345')
                ->setStreet('street')
        );
        $configManager->flush();
    }

    private function assertResponseHasErrorMessage(string $message): void
    {
        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertFalse($result['success']);
        self::assertEquals($message, $result['message']);
    }
}
