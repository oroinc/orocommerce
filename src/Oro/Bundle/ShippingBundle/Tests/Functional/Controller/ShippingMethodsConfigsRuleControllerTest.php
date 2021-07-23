<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\ShippingBundle\Tests\Functional\Helper\FlatRateIntegrationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DomCrawler\Form;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @group CommunityEdition
 */
class ShippingMethodsConfigsRuleControllerTest extends WebTestCase
{
    use FlatRateIntegrationTrait;

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    /**
     * @var Translator;
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadShippingMethodsConfigsRulesWithConfigs::class,
                LoadUserData::class
            ]
        );
        $this->shippingMethodProvider = static::getContainer()->get('oro_shipping.shipping_method_provider');
        $this->translator = static::getContainer()->get('translator');
    }

    public function testIndexWithoutCreate()
    {
        $this->initClient([], static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertEquals(0, $crawler->selectLink('Create Shipping Rule')->count());
    }

    /**
     * @return string
     */
    public function testCreate()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_create'));

        /** @var Form $form */
        $form = $crawler
            ->selectButton('Save and Close')
            ->form();

        $name = 'New Rule';

        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule']['name'] = $name;
        $formValues['oro_shipping_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_shipping_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_shipping_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_shipping_methods_configs_rule']['destinations'] =
            [
                [
                    'postalCodes' => '54321',
                    'country' => 'FR',
                    'region' => 'FR-75'
                ]
            ];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] =
            [
                [
                    'method' => $this->getFlatRateIdentifier(),
                    'options' => '',
                    'typeConfigs' => [
                        [
                            'enabled' => '1',
                            'type' => 'primary',
                            'options' => [
                                'price' => 12,
                                'handling_fee' => null,
                                'type' => 'per_item',
                            ],
                        ]
                    ]
                ]
            ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        static::assertStringContainsString('Shipping rule has been saved', $html);
        static::assertStringContainsString('No', $html);

        return $name;
    }

    /**
     * @depends testCreate
     *
     * @param string $name
     */
    public function testIndex($name)
    {
        $auth = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('shipping-methods-configs-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Shipping Rule')->attr('href');
        static::assertEquals($this->getUrl('oro_shipping_methods_configs_rule_create'), $href);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'shipping-methods-configs-rule-grid',
                'shipping-methods-configs-rule-grid[_sort_by][id]' => 'ASC',
            ]
        );

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        $shippingRule = $this->getShippingMethodsConfigsRuleByName($name);

        $shipMethods = $shippingRule->getMethodConfigs();
        $shipMethodsLabels = [];
        foreach ($shipMethods as $method) {
            $shipMethodsLabels[] = $this->translator
                ->trans($this->shippingMethodProvider->getShippingMethod($method->getMethod())->getLabel());
        }

        $expectedData =
            [
                'data' =>
                    [
                        [
                            'id' => $shippingRule->getId(),
                            'name' => $shippingRule->getRule()->getName(),
                            'enabled' => $shippingRule->getRule()->isEnabled(),
                            'sortOrder' => $shippingRule->getRule()->getSortOrder(),
                            'currency' => $shippingRule->getCurrency(),
                            'expression' => $shippingRule->getRule()->getExpression(),
                            'methodConfigs' => $shipMethodsLabels,
                            'destinations' => implode('</br>', $shippingRule->getDestinations()->getValues()),
                        ],
                    ],
                'columns' =>
                    [
                        'id',
                        'name',
                        'enabled',
                        'sortOrder',
                        'currency',
                        'expression',
                        'methodConfigs',
                        'destinations',
                        'disable_link',
                        'enable_link',
                        'view_link',
                        'action_configuration',
                    ],
            ];

        if (isset($expectedData['columns'])) {
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            $this->assertEquals($expectedColumns, $testedColumns);
        }

        //in case upgrade from commerce_beta1
        //$i should starts from 1 because we have default shipping rule loaded with ORM data migration
        $initKey = count($data) > 1 ? 1 : 0;
        $expectedDataCount = count($expectedData['data']);
        for ($i = $initKey; $i < $expectedDataCount; $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                $this->assertArrayHasKey($key, $data[$i]);
                switch ($key) {
                    case 'methodConfigs':
                        foreach ($value as $methodLabel) {
                            $this->assertContains($methodLabel, $data[$i][$key]);
                        }
                        break;
                    default:
                        $this->assertEquals(trim($value), trim($data[$i][$key]));
                }
            }
        }
    }

    /**
     * @depends testCreate
     *
     * @param string $name
     */
    public function testView($name)
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($name);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_view', ['id' => $shippingRule->getId()])
        );

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        static::assertStringContainsString($shippingRule->getRule()->getName(), $html);
        $destination = $shippingRule->getDestinations();
        static::assertStringContainsString((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->shippingMethodProvider
            ->getShippingMethod($methodConfigs[0]->getMethod())
            ->getLabel();
        static::assertStringContainsString($this->translator->trans($label), $html);
    }

    /**
     * @depends testCreate
     *
     * @param string $name
     *
     * @return ShippingMethodsConfigsRule|object|null
     */
    public function testUpdate($name)
    {
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($name);

        $this->assertNotEmpty($shippingRule);

        $id = $shippingRule->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $id])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $newName = 'New name for new rule';
        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule']['name'] = $newName;
        $formValues['oro_shipping_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_shipping_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_shipping_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_shipping_methods_configs_rule']['destinations'] =
            [
                [
                    'postalCodes' => '54321',
                    'country' => 'TH',
                    'region' => 'TH-83'
                ]
            ];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] =
            [
                [
                    'method' => $this->getFlatRateIdentifier(),
                    'options' => '',
                    'typeConfigs' => [
                        [
                            'enabled' => '1',
                            'type' => 'primary',
                            'options' => [
                                'price' => 24,
                                'handling_fee' => null,
                                'type' => 'per_order',
                            ],
                        ]
                    ]
                ]
            ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        static::assertStringContainsString('Shipping rule has been saved', $html);

        $shippingRule = $this->getShippingMethodsConfigsRuleByName($newName);
        static::assertEquals($id, $shippingRule->getId());

        $destination = $shippingRule->getDestinations();
        static::assertEquals('TH', $destination[0]->getCountry()->getIso2Code());
        static::assertEquals('TH-83', $destination[0]->getRegion()->getCombinedCode());
        static::assertEquals('54321', $destination[0]->getPostalCodes()->current()->getName());
        $methodConfigs = $shippingRule->getMethodConfigs();
        static::assertEquals($this->getFlatRateIdentifier(), $methodConfigs[0]->getMethod());
        static::assertEquals(
            24,
            $methodConfigs[0]->getTypeConfigs()[0]->getOptions()['price']
        );
        static::assertFalse($shippingRule->getRule()->isEnabled());

        return $shippingRule;
    }

    /**
     * @depends testUpdate
     */
    public function testCancel(ShippingMethodsConfigsRule $shippingRule)
    {
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($shippingRule->getRule()->getName());

        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        $link = $crawler->selectLink('Cancel')->link();
        $this->client->click($link);
        $response = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        static::assertStringContainsString($shippingRule->getRule()->getName(), $html);
        $destination = $shippingRule->getDestinations();
        static::assertStringContainsString((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->shippingMethodProvider
            ->getShippingMethod($methodConfigs[0]->getMethod())
            ->getLabel();
        static::assertStringContainsString($this->translator->trans($label), $html);
    }

    /**
     * @depends testUpdate
     *
     * @param ShippingMethodsConfigsRule $shippingRule
     *
     * @return ShippingMethodsConfigsRule
     */
    public function testUpdateRemoveDestination(ShippingMethodsConfigsRule $shippingRule)
    {
        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['destinations'] = [];

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $shippingRule = $this->getEntityManager()->find(
            'OroShippingBundle:ShippingMethodsConfigsRule',
            $shippingRule->getId()
        );
        static::assertCount(0, $shippingRule->getDestinations());

        return $shippingRule;
    }

    public function testStatusDisableMass()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        /** @var ShippingMethodsConfigsRule $shippingRule1 */
        $shippingRule1 = $this->getReference('shipping_rule.1');
        /** @var ShippingMethodsConfigsRule $shippingRule2 */
        $shippingRule2 = $this->getReference('shipping_rule.2');
        $url = $this->getUrl(
            'oro_status_shipping_rule_massaction',
            [
                'gridName' => 'shipping-methods-configs-rule-grid',
                'actionName' => 'disable',
                'inset' => 1,
                'values' => sprintf(
                    '%s,%s',
                    $shippingRule1->getId(),
                    $shippingRule2->getId()
                )
            ]
        );
        $this->ajaxRequest('POST', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertSame(2, $data['count']);
        $this->assertFalse(
            $this
                ->getShippingMethodsConfigsRuleById($shippingRule1->getId())
                ->getRule()
                ->isEnabled()
        );
        $this->assertFalse(
            $this
                ->getShippingMethodsConfigsRuleById($shippingRule2->getId())
                ->getRule()
                ->isEnabled()
        );
    }

    /**
     * @depends testStatusDisableMass
     */
    public function testStatusEnableMass()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        /** @var ShippingMethodsConfigsRule $shippingRule1 */
        $shippingRule1 = $this->getReference('shipping_rule.1');
        /** @var ShippingMethodsConfigsRule $shippingRule2 */
        $shippingRule2 = $this->getReference('shipping_rule.2');
        $url = $this->getUrl(
            'oro_status_shipping_rule_massaction',
            [
                'gridName' => 'shipping-methods-configs-rule-grid',
                'actionName' => 'enable',
                'inset' => 1,
                'values' => sprintf(
                    '%s,%s',
                    $shippingRule1->getId(),
                    $shippingRule2->getId()
                )
            ]
        );
        $this->ajaxRequest('POST', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertSame(2, $data['count']);
        $this->assertTrue(
            $this
                ->getShippingMethodsConfigsRuleById($shippingRule1->getId())
                ->getRule()
                ->isEnabled()
        );
        $this->assertTrue(
            $this
                ->getShippingMethodsConfigsRuleById($shippingRule2->getId())
                ->getRule()
                ->isEnabled()
        );
    }

    public function testShippingMethodsConfigsRuleEditWOPermission()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        static::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testShippingMethodsConfigsRuleEdit()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR);
        $this->initClient([], $authParams);

        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $rule = $shippingRule->getRule();
        $form['oro_shipping_methods_configs_rule[rule][enabled]'] = !$rule->isEnabled();
        $form['oro_shipping_methods_configs_rule[rule][name]'] = $rule->getName() . ' new name';
        $form['oro_shipping_methods_configs_rule[rule][sortOrder]'] = $rule->getSortOrder() + 1;
        $form['oro_shipping_methods_configs_rule[currency]'] = $shippingRule->getCurrency() === 'USD' ? 'EUR' : 'USD';
        $form['oro_shipping_methods_configs_rule[rule][stopProcessing]'] = !$rule->isStopProcessing();
        $form['oro_shipping_methods_configs_rule[rule][expression]'] = $rule->getExpression() . ' && true';
        $form['oro_shipping_methods_configs_rule[destinations][0][postalCodes]'] = '11111';
        $form['oro_shipping_methods_configs_rule[methodConfigs][0][typeConfigs][0][options][price]'] = 12;
        $form['oro_shipping_methods_configs_rule[methodConfigs][0][typeConfigs][0][enabled]'] = true;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Shipping rule has been saved', $crawler->html());
    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid(
            ['gridName' => 'shipping-methods-configs-rule-grid'],
            [],
            true
        );

        $result = static::getJsonResponseContent($response, 200);

        $this->assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    /**
     * @return ObjectManager|null
     */
    protected function getEntityManager()
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroShippingBundle:ShippingMethodsConfigsRule');
    }

    /**
     * @param string $name
     *
     * @return ShippingMethodsConfigsRule|null
     */
    protected function getShippingMethodsConfigsRuleByName($name)
    {
        /** @var RuleInterface $rule */
        $rule = $this
            ->getEntityManager()
            ->getRepository('OroRuleBundle:Rule')
            ->findOneBy(['name' => $name]);

        return $this
            ->getEntityManager()
            ->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->findOneBy(['rule' => $rule]);
    }

    /**
     * @param int $id
     *
     * @return ShippingMethodsConfigsRule|null
     */
    protected function getShippingMethodsConfigsRuleById($id)
    {
        return $this->getEntityManager()
            ->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->find($id);
    }
}
