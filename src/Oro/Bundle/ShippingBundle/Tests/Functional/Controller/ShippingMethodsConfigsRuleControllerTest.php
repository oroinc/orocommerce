<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DomCrawler\Form;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolation
 * @group CommunityEdition
 */
class ShippingMethodsConfigsRuleControllerTest extends WebTestCase
{
    const NAME = 'New rule';

    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @var Translator;
     */
    protected $translator;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadShippingMethodsConfigsRules::class, LoadUserData::class]);
        $this->registry = static::getContainer()->get('oro_shipping.shipping_method.registry');
        $this->translator = static::getContainer()->get('translator');
    }

    public function testIndex()
    {
        $auth = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertContains('shipping-methods-configs-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Shipping Rule')->attr('href');
        static::assertEquals($this->getUrl('oro_shipping_methods_configs_rule_create'), $href);

        $response = $this->client->requestGrid([
            'gridName' => 'shipping-methods-configs-rule-grid',
            'shipping-methods-configs-rule-grid[_sort_by][id]' => 'ASC',
        ]);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $shipMethods = $shippingRule->getMethodConfigs();
        $shipMethodsLabels = [];
        foreach ($shipMethods as $method) {
            $shipMethodsLabels[] = $this->translator
                ->trans($this->registry->getShippingMethod($method->getMethod())->getLabel());
        }

        $expectedData = [
            'data' => [
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
            'columns' => [
                'id',
                'name',
                'enabled',
                'sortOrder',
                'currency',
                'expression',
                'methodConfigs',
                'destinations',
                'delete_link',
                'disable_link',
                'enable_link',
                'update_link',
                'view_link',
                'action_configuration'
            ],
        ];

        if (isset($expectedData['columns'])) {
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            $this->assertEquals($expectedColumns, $testedColumns);
        }

        $expectedDataCount = count($expectedData['data']);
        for ($i = 0; $i < $expectedDataCount; $i++) {
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

    public function testIndexWithoutCreate()
    {
        $this->initClient([], static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertEquals(0, $crawler->selectLink('Create Shipping Rule')->count());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $name = 'New Rule';

        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule']['name'] = $name;
        $formValues['oro_shipping_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_shipping_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_shipping_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_shipping_methods_configs_rule']['destinations'] = [
            [
                'postalCodes' => '54321',
                'country' => 'FR',
                'region' => 'FR-75'
            ]
        ];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] = [
            [
                'method' => FlatRateShippingMethod::IDENTIFIER,
                'options' => [],
                'typeConfigs' => [
                    [
                        'enabled' => '1',
                        'type' => FlatRateShippingMethodType::IDENTIFIER,
                        'options' => [
                            FlatRateShippingMethodType::PRICE_OPTION => 12,
                            FlatRateShippingMethodType::HANDLING_FEE_OPTION => null,
                            FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ITEM_TYPE,
                        ],
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        $this->assertContains('Shipping rule has been saved', $html);
        $this->assertContains('No', $html);

        return $name;
    }

    /**
     * @depends testCreate
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

        $this->assertContains($shippingRule->getName(), $html);
        $this->checkCurrenciesOnPage($shippingRule->getCurrency(), $html);
        $destination = $shippingRule->getDestinations();
        $this->assertContains((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->registry->getShippingMethod($methodConfigs[0]->getMethod())->getLabel();
        $this->assertContains($this->translator->trans($label), $html);
    }

    protected function checkCurrenciesOnPage($currency, $html)
    {
        return true;
    }

    protected function checkCurrency($currency)
    {
        return true;
    }

    /**
     * @depends testCreate
     * @param string $name
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

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($shippingRule->getCurrency(), $html);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $newName = 'New name for new rule';
        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['rule']['name'] = $newName;
        $formValues['oro_shipping_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_shipping_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_shipping_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_shipping_methods_configs_rule']['destinations'] = [
            [
                'postalCodes' => '54321',
                'country' => 'TH',
                'region' => 'TH-83'
            ]
        ];
        $formValues['oro_shipping_methods_configs_rule']['methodConfigs'] = [
            [
                'method' => FlatRateShippingMethod::IDENTIFIER,
                'options' => [],
                'typeConfigs' => [
                    [
                        'enabled' => '1',
                        'type' => FlatRateShippingMethodType::IDENTIFIER,
                        'options' => [
                            FlatRateShippingMethodType::PRICE_OPTION => 24,
                            FlatRateShippingMethodType::HANDLING_FEE_OPTION => null,
                            FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ORDER_TYPE,
                        ],
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        static::assertContains('Shipping rule has been saved', $html);

        $shippingRule = $this->getShippingMethodsConfigsRuleByName($newName);
        static::assertEquals($id, $shippingRule->getId());

        $this->checkCurrency($shippingRule->getCurrency());
        $destination = $shippingRule->getDestinations();
        static::assertEquals('TH', $destination[0]->getCountry()->getIso2Code());
        static::assertEquals('TH-83', $destination[0]->getRegion()->getCombinedCode());
        static::assertEquals('54321', $destination[0]->getPostalCode());
        $methodConfigs = $shippingRule->getMethodConfigs();
        static::assertEquals(FlatRateShippingMethod::IDENTIFIER, $methodConfigs[0]->getMethod());
        static::assertEquals(
            24,
            $methodConfigs[0]->getTypeConfigs()[0]->getOptions()[FlatRateShippingMethodType::PRICE_OPTION]
        );
        static::assertFalse($shippingRule->isEnabled());

        return $shippingRule;
    }

    /**
     * @depends testUpdate
     * @param ShippingMethodsConfigsRule $shippingRule
     */
    public function testCancel(ShippingMethodsConfigsRule $shippingRule)
    {
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($shippingRule->getRule()->getName());

        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($shippingRule->getCurrency(), $html);

        $link = $crawler->selectLink('Cancel')->link();
        $this->client->click($link);
        $response = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        static::assertContains($shippingRule->getName(), $html);
        $this->checkCurrenciesOnPage($shippingRule->getCurrency(), $html);
        $destination = $shippingRule->getDestinations();
        static::assertContains((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->registry->getShippingMethod($methodConfigs[0]->getMethod())->getLabel();
        static::assertContains($this->translator->trans($label), $html);
    }

    /**
     * @depends testUpdate
     * @param ShippingMethodsConfigsRule $shippingRule
     * @return object|ShippingMethodsConfigsRule
     */
    public function testUpdateRemoveDestination(ShippingMethodsConfigsRule $shippingRule)
    {
        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($shippingRule->getCurrency(), $html);

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
        $shippingRule1 = $this->getReference('shipping_rule.1');
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
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertSame(2, $data['count']);
        $this->assertFalse(
            $this->getShippingMethodsConfigsRuleByName($shippingRule1->getName())->isEnabled()
        );
        $this->assertFalse(
            $this->getShippingMethodsConfigsRuleByName($shippingRule2->getName())->isEnabled()
        );
    }

    /**
     * @depends testStatusDisableMass
     */
    public function testStatusEnableMass()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $shippingRule1 = $this->getReference('shipping_rule.1');
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
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful']);
        $this->assertSame(2, $data['count']);
        $this->assertTrue(
            $this->getShippingMethodsConfigsRuleByName($shippingRule1->getName())->isEnabled()
        );
        $this->assertTrue(
            $this->getShippingMethodsConfigsRuleByName($shippingRule2->getName())->isEnabled()
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

        $form['oro_shipping_methods_configs_rule[rule][enabled]'] = !$shippingRule->getRule()->isEnabled();
        $form['oro_shipping_methods_configs_rule[rule][name]'] = $shippingRule->getRule()->getName().' new name';
        $form['oro_shipping_methods_configs_rule[rule][sortOrder]'] = $shippingRule->getRule()->getSortOrder() + 1;
        $form['oro_shipping_methods_configs_rule[currency]'] = $shippingRule->getCurrency() === 'USD' ? 'EUR' : 'USD';
        $form['oro_shipping_methods_configs_rule[rule][stopProcessing]'] = !$shippingRule->getRule()->isStopProcessing();
        $form['oro_shipping_methods_configs_rule[rule][expression]'] = $shippingRule->getRule()->getExpression().' new data';
        $form['oro_shipping_methods_configs_rule[destinations][0][postalCode]'] = '11111';
        $form['oro_shipping_methods_configs_rule[methodConfigs][0][typeConfigs][0][options][price]'] = 12;
        $form['oro_shipping_methods_configs_rule[methodConfigs][0][typeConfigs][0][enabled]'] = true;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertContains('Shipping rule has been saved', $crawler->html());
    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid([
            'gridName' => 'shipping-methods-configs-rule-grid'
        ], [], true);

        $result = static::getJsonResponseContent($response, 200);

        $this->assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|mixed|null|object
     */
    protected function getEntityManager()
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroShippingBundle:ShippingMethodsConfigsRule');
    }

    /**
     * @param string $name
     * @return ShippingMethodsConfigsRule|object|null
     */
    protected function getShippingMethodsConfigsRuleByName($name)
    {
        return $this->getEntityManager()
            ->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->findOneBy(['name' => $name]);
    }
}
