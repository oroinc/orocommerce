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
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testCreateAction()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_create'));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

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
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Integration saved', $crawler->html());

        $settings = $this->getFedexIntegrationSettings();
        $this->assertSettingsCorrect($settings, $settingsData);

        $serviceIds[] = $settings->getShippingServices()[0]->getId();
        $serviceIds[] = $settings->getShippingServices()[1]->getId();

        self::assertContains(1, $serviceIds);
        self::assertContains(2, $serviceIds);
    }

    public function testIndexAction()
    {
        $this->createFedexIntegrationSettings();

        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('oro-integration-grid', $crawler->html());
        self::assertStringContainsString('fedex-logo.png', $crawler->html());
        self::assertStringContainsString('FedEx', $crawler->html());
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

    private function assertSettingsCorrect(FedexIntegrationSettings $settings, array $settingsData): void
    {
        self::assertSame($settingsData['key'], $settings->getKey());
        self::assertSame(
            $settingsData['password'],
            self::getContainer()->get('oro_security.encoder.default')->decryptData($settings->getPassword())
        );
        self::assertSame($settingsData['accountNumber'], $settings->getAccountNumber());
        self::assertSame($settingsData['meterNumber'], $settings->getMeterNumber());
        self::assertSame($settingsData['pickupType'], $settings->getPickupType());
        self::assertSame($settingsData['unitOfWeight'], $settings->getUnitOfWeight());
        self::assertCount(count($settingsData['shippingServices']), $settings->getShippingServices());
        self::assertSame(
            $settingsData['labels']['values']['default'],
            $settings->getLabels()[0]->getString()
        );
    }
}
