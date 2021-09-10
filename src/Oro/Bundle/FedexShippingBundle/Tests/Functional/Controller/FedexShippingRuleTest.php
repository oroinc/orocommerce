<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper\FedexIntegrationTrait;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class FedexShippingRuleTest extends WebTestCase
{
    use FedexIntegrationTrait;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->createFedexIntegrationSettings();
    }

    public function testCreate()
    {
        static::getContainer()->get('doctrine')->getManager()->clear();

        $methodIdentifier = $this->getMethodIdentifier();

        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_methods_configs_rule_create'),
            [
                'oro_shipping_methods_configs_rule' => [
                    'methodConfigs' => [['method' => $methodIdentifier]],
                ],
                'update_methods_flag' => true,
            ]
        );

        $response = $this->client->getResponse();

        static::assertStringContainsString('fedex-logo.png', $response->getContent());
        static::assertStringContainsString('FedEx Europe First International Priority', $response->getContent());
        static::assertStringContainsString('FedEx 2 Day', $response->getContent());
        static::assertStringContainsString('FedEx 2 Day Freight', $response->getContent());

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
                    'method' => $methodIdentifier,
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

        $this->client->followRedirects();

        $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_methods_configs_rule_create'),
            $this->createFormValues($form, $configData)
        );
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Shipping rule has been saved', $this->client->getResponse()->getContent());

        $config = $this->getMethodConfig($methodIdentifier);

        $this->assertMethodConfigCorrect($config, $configData);
    }

    public function testIndex()
    {
        $this->createShippingMethodConfig();

        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('shipping-methods-configs-rule-grid', $crawler->html());
        static::assertStringContainsString('fedex', $crawler->html());
    }

    private function createFormValues(Form $form, array $configData): array
    {
        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule'] = $configData['rule'];
        $formValues['oro_shipping_methods_configs_rule']['currency'] = $configData['currency'];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] = $configData['methodConfigs'];

        return $formValues;
    }

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
     * @param string $methodIdentifier
     * @return ShippingMethodConfig|null
     */
    private function getMethodConfig(string $methodIdentifier)
    {
        $config = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroShippingBundle:ShippingMethodConfig')
            ->findByMethod($methodIdentifier);

        if (empty($config)) {
            return null;
        }

        return $config[0];
    }

    private function getMethodIdentifier(): string
    {
        /** @var IntegrationIdentifierGeneratorInterface $methodIdGenerator */
        $methodIdGenerator = static::getContainer()->get('oro_fedex_shipping.integration.identifier_generator');

        return $methodIdGenerator->generateIdentifier($this->getFedexIntegrationSettings()->getChannel());
    }
}
