<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper\FedexIntegrationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * @dbIsolationPerTest
 */
class FedexIntegrationTest extends WebTestCase
{
    use FedexIntegrationTrait;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
    }

    public function testCreateAction()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_create'));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $settingsData = [
            'labels' => [
                'values' => [
                    'default' => 'fedex',
                    'localizations' => [
                        '1' => ['value' => 'fedex']
                    ]
                ]
            ],
            'fedexTestMode' => true,
            'key' => 'key',
            'password' => 'password',
            'accountNumber' => 'accountNumber',
            'meterNumber' => 'meterNumber',
            'pickupType' => FedexIntegrationSettings::PICKUP_TYPE_DROP_BOX,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_LB,
            'shippingServices' => [1, 2],
        ];

        $this->client->followRedirects();

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $this->createFormValues($form, $settingsData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Integration saved', $crawler->html());

        $settings = $this->getFedexIntegrationSettings();
        $this->assertSettingsCorrect($settings, $settingsData);

        $serviceIds[] = $settings->getShippingServices()[0]->getId();
        $serviceIds[] = $settings->getShippingServices()[1]->getId();

        static::assertTrue(in_array(1, $serviceIds, true));
        static::assertTrue(in_array(2, $serviceIds, true));
    }

    public function testIndexAction()
    {
        $this->createFedexIntegrationSettings();

        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('oro-integration-grid', $crawler->html());
        static::assertStringContainsString('fedex-logo.png', $crawler->html());
        static::assertStringContainsString('FedEx', $crawler->html());
    }

    private function createFormValues(Form $form, array $settings): array
    {
        $formValues = $form->getPhpValues();
        $formValues['oro_integration_channel_form']['type'] = 'fedex';
        $formValues['oro_integration_channel_form']['name'] = 'fedex';
        $formValues['oro_integration_channel_form']['transportType'] = 'fedex';
        $formValues['oro_integration_channel_form']['transport'] = $settings;

        return $formValues;
    }

    private function assertSettingsCorrect(FedexIntegrationSettings $settings, array $settingsData)
    {
        static::assertSame($settingsData['key'], $settings->getKey());
        static::assertSame(
            $settingsData['password'],
            static::getContainer()->get('oro_security.encoder.default')->decryptData($settings->getPassword())
        );
        static::assertSame($settingsData['accountNumber'], $settings->getAccountNumber());
        static::assertSame($settingsData['meterNumber'], $settings->getMeterNumber());
        static::assertSame($settingsData['pickupType'], $settings->getPickupType());
        static::assertSame($settingsData['unitOfWeight'], $settings->getUnitOfWeight());
        static::assertCount(count($settingsData['shippingServices']), $settings->getShippingServices());
        static::assertSame(
            $settingsData['labels']['values']['default'],
            $settings->getLabels()[0]->getString()
        );
    }
}
