<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper\FedexIntegrationTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * @dbIsolationPerTest
 */
class FedexIntegrationTest extends WebTestCase
{
    use FedexIntegrationTrait;

    protected function setUp()
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

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $this->createFormValues($form, $settingsData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('Integration saved', $crawler->html());

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
        static::assertContains('oro-integration-grid', $crawler->html());
        static::assertContains('fedex-logo.png', $crawler->html());
        static::assertContains('FedEx', $crawler->html());
    }

    public function testUpdateAction()
    {
        $this->createFedexIntegrationSettings();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_update', [
                'id' => $this->getFedexIntegrationSettings()->getId()
            ])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        $settingsData = [
            'labels' => [
                'values' => [
                    'default' => 'fedex New',
                    'localizations' => [
                        '1' => ['value' => 'fedex New']
                    ]
                ]
            ],
            'fedexTestMode' => false,
            'key' => 'key2',
            'password' => 'password2',
            'accountNumber' => 'accountNumber2',
            'meterNumber' => 'meterNumber2',
            'pickupType' => FedexIntegrationSettings::PICKUP_TYPE_REGULAR,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            'shippingServices' => [3],
        ];

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $this->createFormValues($form, $settingsData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('Integration saved', $crawler->html());

        $settings = $this->getFedexIntegrationSettings();
        $this->assertSettingsCorrect($settings, $settingsData);

        static::assertSame(3, $settings->getShippingServices()[0]->getId());
    }

    public function testDeactivateAction()
    {
        $this->createFedexIntegrationSettings();

        $this->client->request(
            'GET',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => 'oro_fedex_integration_deactivate_without_rules'
            ]),
            [
                'entityClass' => Channel::class,
                'entityId' => $this->getFedexIntegrationSettings()->getId(),
            ]
        );

        $response = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($response, 200);
        static::assertContains('"success":true', $response->getContent());

        static::assertFalse($this->getFedexIntegrationSettings()->getChannel()->isEnabled());
    }

    public function testActivateAction()
    {
        $this->createFedexIntegrationSettings(false);

        $this->client->request(
            'GET',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => 'oro_integration_activate'
            ]),
            [
                'entityClass' => Channel::class,
                'entityId' => $this->getFedexIntegrationSettings()->getId(),
            ]
        );

        $response = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($response, 200);
        static::assertContains('"success":true', $response->getContent());

        static::assertTrue($this->getFedexIntegrationSettings()->getChannel()->isEnabled());
    }

    public function testDeleteAction()
    {
        $this->createFedexIntegrationSettings();

        $this->client->request(
            'GET',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => 'oro_fedex_integration_delete_without_rules'
            ]),
            [
                'entityClass' => Channel::class,
                'entityId' => $this->getFedexIntegrationSettings()->getId(),
            ]
        );

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, 302);

        static::assertNull($this->getFedexIntegrationSettings());
    }

    /**
     * @param Form  $form
     * @param array $settings
     *
     * @return array
     */
    private function createFormValues(Form $form, array $settings): array
    {
        $formValues = $form->getPhpValues();
        $formValues['oro_integration_channel_form']['type'] = 'fedex';
        $formValues['oro_integration_channel_form']['name'] = 'fedex';
        $formValues['oro_integration_channel_form']['transportType'] = 'fedex';
        $formValues['oro_integration_channel_form']['transport'] = $settings;

        return $formValues;
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param array                    $settingsData
     */
    private function assertSettingsCorrect(FedexIntegrationSettings $settings, array $settingsData)
    {
        static::assertSame($settingsData['key'], $settings->getKey());
        static::assertSame(
            $settingsData['password'],
            static::getContainer()->get('oro_security.encoder.mcrypt')->decryptData($settings->getPassword())
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
