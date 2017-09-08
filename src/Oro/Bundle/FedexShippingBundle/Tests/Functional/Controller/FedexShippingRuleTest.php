<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper\FedexIntegrationTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

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
        static::assertContains('FedEx 2 Day', $response->getContent());
        static::assertContains('FedEx 2 Day Freight', $response->getContent());

        $form = $crawler->selectButton('Save and Close')->form();

        $configData = [
            'rule' => [
                'enabled' => true,
                'name' => 'fedex',
                'sortOrder' => '1',
                'expression' => '1 = 0',
            ],
            'currency' => 'USD',
            'methodConfigs' => [
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
                            'enabled' => false,
                        ],
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_methods_configs_rule_create'),
            $this->createFormValues($form, $configData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('Shipping rule has been saved', $this->client->getResponse()->getContent());

        $config = $this->getMethodConfig();

        $this->assertMethodConfigCorrect($config, $configData);
    }

    public function testIndex()
    {
        $this->createShippingMethodConfig();

        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('shipping-methods-configs-rule-grid', $crawler->html());
        static::assertContains('fedex', $crawler->html());
    }

    public function testUpdate()
    {
        $this->createShippingMethodConfig();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', [
                'id' => $this->getMethodConfig()->getId()
            ])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        $configData = [
            'rule' => [
                'enabled' => false,
                'name' => 'fedex 2',
                'sortOrder' => '3',
                'expression' => '1 != 0',
            ],
            'currency' => 'USD',
            'methodConfigs' => [
                [
                    'method' => $this->getMethodIdentifier(),
                    'options' => ['surcharge' => 15],
                    'typeConfigs' => [
                        [
                            'type' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                            'options' => ['surcharge' => ''],
                            'enabled' => false,
                        ],
                        [
                            'type' => 'FEDEX_1_DAY_FREIGHT',
                            'options' => ['surcharge' => ''],
                            'enabled' => true,
                        ],
                        [
                            'type' => 'FEDEX_2_DAY',
                            'options' => ['surcharge' => 30],
                            'enabled' => false,
                        ],
                        [
                            'type' => 'FEDEX_2_DAY_AM',
                            'options' => ['surcharge' => 6],
                            'enabled' => true,
                        ],
                        [
                            'type' => 'FEDEX_2_DAY_FREIGHT',
                            'options' => ['surcharge' => ''],
                            'enabled' => false,
                        ],
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $this->createFormValues($form, $configData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('Shipping rule has been saved', $this->client->getResponse()->getContent());

        $config = $this->getMethodConfig();

        $this->assertMethodConfigCorrect($config, $configData);
    }

    public function testDeactivateIntegration()
    {
        $this->createShippingMethodConfig();

        $this->client->request(
            'GET',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => 'oro_fedex_integration_deactivate'
            ]),
            [
                'entityClass' => Channel::class,
                'entityId' => $this->getFedexIntegrationSettings()->getId(),
            ]
        );

        $response = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($response, 200);
        static::assertContains('"success":true', $response->getContent());

        static::assertFalse($this->getMethodConfig()->getMethodConfigsRule()->getRule()->isEnabled());
    }

    public function testDeleteIntegration()
    {
        $this->createShippingMethodConfig();

        $this->client->request(
            'GET',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => 'oro_fedex_integration_delete'
            ]),
            [
                'entityClass' => Channel::class,
                'entityId' => $this->getFedexIntegrationSettings()->getId(),
            ]
        );

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, 302);

        static::assertNull($this->getMethodConfig());
    }

    /**
     * @param Form  $form
     * @param array $configData
     *
     * @return array
     */
    private function createFormValues(Form $form, array $configData): array
    {
        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule'] = $configData['rule'];
        $formValues['oro_shipping_methods_configs_rule']['currency'] = $configData['currency'];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] = $configData['methodConfigs'];

        return $formValues;
    }

    /**
     * @param ShippingMethodConfig $config
     * @param array                $configData
     */
    private function assertMethodConfigCorrect(ShippingMethodConfig $config, array $configData)
    {
        static::assertSame($configData['methodConfigs'][0]['method'], $config->getMethod());
        static::assertSame($configData['rule']['name'], $config->getMethodConfigsRule()->getRule()->getName());
        static::assertSame(
            (int) $configData['rule']['sortOrder'],
            $config->getMethodConfigsRule()->getRule()->getSortOrder()
        );
        static::assertSame(
            $configData['rule']['expression'],
            $config->getMethodConfigsRule()->getRule()->getExpression()
        );
        static::assertSame($configData['currency'], $config->getMethodConfigsRule()->getCurrency());
        static::assertEquals($configData['methodConfigs'][0]['options'], $config->getOptions());

        static::assertCount(count($configData['methodConfigs'][0]['typeConfigs']), $config->getTypeConfigs());

        foreach ($configData['methodConfigs'][0]['typeConfigs'] as $typeConfig) {
            $this->assertMethodTypeConfigCreated(
                $typeConfig['type'],
                $typeConfig['options'],
                $typeConfig['enabled']
            );
        }
    }

    /**
     * @param string $type
     * @param array  $options
     * @param bool   $enabled
     */
    private function assertMethodTypeConfigCreated(string $type, array $options, bool $enabled)
    {
        static::assertNotNull(
            static::getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                ->findBy([
                    'type' => $type,
                    'options' => $options,
                    'enabled' => $enabled
                ])
        );
    }

    private function createShippingMethodConfig()
    {
        $rule = new Rule();
        $rule
            ->setName('fedex')
            ->setEnabled(true)
            ->setSortOrder(1)
            ->setExpression('1 = 0');

        /** @var Organization $organization */
        $organization = $this->getAdminUser()->getOrganization();

        $configRule = new ShippingMethodsConfigsRule();
        $configRule
            ->setCurrency('USD')
            ->setRule($rule)
            ->setOrganization($organization);

        $type = new ShippingMethodTypeConfig();
        $type
            ->setType('EUROPE_FIRST_INTERNATIONAL_PRIORITY')
            ->setOptions([FedexShippingMethod::OPTION_SURCHARGE => 1])
            ->setEnabled(true);

        $config = new ShippingMethodConfig();
        $config
            ->setOptions([FedexShippingMethod::OPTION_SURCHARGE => 10])
            ->setMethodConfigsRule($configRule)
            ->setMethod($this->getMethodIdentifier())
            ->addTypeConfig($type);

        static::getContainer()->get('doctrine')->getManager()->persist($configRule);
        static::getContainer()->get('doctrine')->getManager()->persist($config);
        static::getContainer()->get('doctrine')->getManager()->flush();
    }

    /**
     * @return ShippingMethodConfig|null
     */
    private function getMethodConfig()
    {
        $config = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroShippingBundle:ShippingMethodConfig')
            ->findAll();

        if (empty($config)) {
            return null;
        }

        return $config[0];
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
