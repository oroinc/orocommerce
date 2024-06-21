<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ValidateConnectionControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testValidateConnectionActionNoShippingOptions(): void
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

    public function testValidateConnectionAction400Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/400.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Bad request was send. Please check the configuration and try to make a request again'
        );
    }

    public function testValidateConnectionAction401Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/401.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Authentication error has occurred. Please check credentials and try to make a request again'
        );
    }

    public function testValidateConnectionAction403Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/403.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Forbidden. Please check credentials and try to make a request again'
        );
    }

    public function testValidateConnectionAction404Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/404.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Not found. Please try to make a request again later'
        );
    }

    public function testValidateConnectionAction500Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/500.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Failure. Please try to make a request again later'
        );
    }

    public function testValidateConnectionAction503Error(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/503.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'Service unavailable. Please try to make a request again later'
        );
    }

    public function testValidateConnectionActionNoPricesError(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/no_prices.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $this->assertResponseHasErrorMessage(
            'No services are available for current configuration, make sure that Shipping Origin configuration'
            . ' is correct in System Configuration -> Shipping -> Shipping Origin'
        );
    }

    public function testValidateConnectionActionOk(): void
    {
        $this->setConfigShippingOrigin();

        self::getContainer()->get('oro_integration.transport.rest.client_factory')->setFixtureFile(
            __DIR__ . '/response/200.yml'
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_fedex_validate_connection', ['channelId' => '0']),
            $this->getRequestFormData()
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertTrue($result['success']);
        self::assertEquals('Connection is valid', $result['message']);
    }

    private function getRequestFormData(): array
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
                    'clientId' => 'testclient',
                    'clientSecret' => 'password',
                    'accountNumber' => 'accountNumber',
                    'pickupType' => 'pickupType',
                    'unitOfWeight' => 'unitOfWeight',
                    'shippingServices' => ['2'],
                ],
            ],
        ];
    }

    private function setConfigShippingOrigin(): void
    {
        $configManager = self::getConfigManager('global');

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

    private function assertResponseHasErrorMessage(string $message)
    {
        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertFalse($result['success']);
        self::assertEquals($message, $result['message']);
    }
}
