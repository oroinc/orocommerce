<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures\LoadFlatRateIntegration;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @group CommunityEdition
 */
class ShippingMethodsConfigsRuleControllerTest extends WebTestCase
{
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadShippingMethodsConfigsRulesWithConfigs::class,
            LoadUserData::class
        ]);
        $this->shippingMethodProvider = self::getContainer()->get('oro_shipping.shipping_method_provider');
        $this->translator = self::getContainer()->get('translator');
    }

    public function testIndexWithoutCreate()
    {
        $this->initClient([], self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertEquals(0, $crawler->selectLink('Create Shipping Rule')->count());
    }

    public function testCreate(): string
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_create'));

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

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        self::assertStringContainsString('Shipping rule has been saved', $html);
        self::assertStringContainsString('No', $html);

        return $name;
    }

    /**
     * @depends testCreate
     */
    public function testIndex(string $name)
    {
        $auth = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('oro_shipping_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('shipping-methods-configs-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Shipping Rule')->attr('href');
        self::assertEquals($this->getUrl('oro_shipping_methods_configs_rule_create'), $href);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'shipping-methods-configs-rule-grid',
                'shipping-methods-configs-rule-grid[_sort_by][id]' => 'ASC',
            ]
        );

        $result = self::getJsonResponseContent($response, 200);

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
                if ('methodConfigs' === $key) {
                    foreach ($value as $methodLabel) {
                        $this->assertContains($methodLabel, $data[$i][$key]);
                    }
                } else {
                    $this->assertEquals(trim($value), trim($data[$i][$key]));
                }
            }
        }
    }

    /**
     * @depends testCreate
     */
    public function testView(string $name)
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($name);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_view', ['id' => $shippingRule->getId()])
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        self::assertStringContainsString($shippingRule->getRule()->getName(), $html);
        $destination = $shippingRule->getDestinations();
        self::assertStringContainsString((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->shippingMethodProvider
            ->getShippingMethod($methodConfigs[0]->getMethod())
            ->getLabel();
        self::assertStringContainsString($this->translator->trans($label), $html);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(string $name): ShippingMethodsConfigsRule
    {
        $shippingRule = $this->getShippingMethodsConfigsRuleByName($name);

        $this->assertNotEmpty($shippingRule);

        $id = $shippingRule->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $id])
        );

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

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        self::assertStringContainsString('Shipping rule has been saved', $html);

        $shippingRule = $this->getShippingMethodsConfigsRuleByName($newName);
        self::assertEquals($id, $shippingRule->getId());

        $destination = $shippingRule->getDestinations();
        self::assertEquals('TH', $destination[0]->getCountry()->getIso2Code());
        self::assertEquals('TH-83', $destination[0]->getRegion()->getCombinedCode());
        self::assertEquals('54321', $destination[0]->getPostalCodes()->current()->getName());
        $methodConfigs = $shippingRule->getMethodConfigs();
        self::assertEquals($this->getFlatRateIdentifier(), $methodConfigs[0]->getMethod());
        self::assertEquals(
            24,
            $methodConfigs[0]->getTypeConfigs()[0]->getOptions()['price']
        );
        self::assertFalse($shippingRule->getRule()->isEnabled());

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

        self::assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        self::assertStringContainsString($shippingRule->getRule()->getName(), $html);
        $destination = $shippingRule->getDestinations();
        self::assertStringContainsString((string)$destination[0], $html);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $label = $this->shippingMethodProvider
            ->getShippingMethod($methodConfigs[0]->getMethod())
            ->getLabel();
        self::assertStringContainsString($this->translator->trans($label), $html);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateRemoveDestination(ShippingMethodsConfigsRule $shippingRule): ShippingMethodsConfigsRule
    {
        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_shipping_methods_configs_rule']['destinations'] = [];

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $shippingRule = $this->getEntityManager()->find(ShippingMethodsConfigsRule::class, $shippingRule->getId());
        self::assertCount(0, $shippingRule->getDestinations());

        return $shippingRule;
    }

    public function testStatusDisableMass()
    {
        $this->initClient([], self::generateBasicAuthHeader());
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
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
        $this->initClient([], self::generateBasicAuthHeader());
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
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
        $authParams = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testShippingMethodsConfigsRuleEdit()
    {
        $authParams = self::generateBasicAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR);
        $this->initClient([], $authParams);

        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shipping_methods_configs_rule_update', ['id' => $shippingRule->getId()])
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

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

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Shipping rule has been saved', $crawler->html());
    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid(
            ['gridName' => 'shipping-methods-configs-rule-grid'],
            [],
            true
        );

        $result = self::getJsonResponseContent($response, 200);

        $this->assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(ShippingMethodsConfigsRule::class);
    }

    private function getShippingMethodsConfigsRuleByName(string $name): ?ShippingMethodsConfigsRule
    {
        $em = $this->getEntityManager();

        return $em->getRepository(ShippingMethodsConfigsRule::class)
            ->findOneBy(['rule' => $em->getRepository(Rule::class)->findOneBy(['name' => $name])]);
    }

    private function getShippingMethodsConfigsRuleById(int $id): ShippingMethodsConfigsRule
    {
        return $this->getEntityManager()
            ->getRepository(ShippingMethodsConfigsRule::class)
            ->find($id);
    }

    private function getFlatRateIdentifier(): string
    {
        return sprintf('flat_rate_%s', $this->getReference(LoadFlatRateIntegration::REFERENCE_FLAT_RATE)->getId());
    }
}
