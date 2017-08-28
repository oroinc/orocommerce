<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper\FedexIntegrationTrait;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FedexShippingRuleTest extends WebTestCase
{
    use FedexIntegrationTrait;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->createFedexIntegrationSettings();
    }

    public function testCreate()
    {
        static::getContainer()->get('doctrine')->getManager()->clear();

        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_methods_configs_rule_create'),
            [
                'oro_shipping_methods_configs_rule' => [
                    'methodConfigs' => [['method' => $this->getMethodIdentifier()]],
                ],
                'update_methods_flag' => true,
            ]
        );

        $response = $this->client->getResponse();

        static::assertContains('fedex-logo.png', $response->getContent());
        static::assertContains('FedEx Europe First International Priority', $response->getContent());
        static::assertContains('FedEx Ground', $response->getContent());
        static::assertContains('FedEx International First', $response->getContent());

        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule'] = [
            'enabled' => true,
            'name' => 'fedex',
            'sortOrder' => '1',
            'expression' => '',
        ];
        $formValues['currency'] = 'USD';
        $formValues['methodConfigs'] = [
            [
                'method' => $this->getMethodIdentifier(),
                'options' => ['surcharge' => 10],
                'typeConfigs' => [
                    [
                        'type' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                        'options' => ['surcharge' => 1],
                        'enabled' => true,
                    ],
                    [
                        'type' => 'FEDEX_1_DAY_FREIGHT',
                        'options' => ['surcharge' => 2],
                        'enabled' => true,
                    ],
                    [
                        'type' => 'FEDEX_2_DAY',
                        'options' => ['surcharge' => 3],
                        'enabled' => true,
                    ],
                    [
                        'type' => 'FEDEX_2_DAY_AM',
                        'options' => ['surcharge' => ''],
                        'enabled' => true,
                    ],
                    [
                        'type' => 'FEDEX_2_DAY_FREIGHT',
                        'options' => ['surcharge' => ''],
                        'enabled' => true,
                    ],
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_methods_configs_rule_create'),
            $formValues
        );

        $response = $this->client->getResponse();

        $rules = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroShippingBundle:ShippingMethodConfig')
            ->findAll();
    }

    /**
     * @return string
     */
    private function getMethodIdentifier(): string
    {
        /** @var IntegrationIdentifierGeneratorInterface $methodIdGenerator */
        $methodIdGenerator = static::getContainer()->get('oro_fedex_shipping.integration.identifier_generator');

        return $methodIdGenerator->generateIdentifier($this->getFedexIntegrationSettings()->getChannel());
    }
}
