<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DomCrawler\Form;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @group CommunityEdition
 */
class PaymentMethodsConfigsRuleControllerTest extends WebTestCase
{
    const NAME = 'New rule';
    const PAYMENT_METHOD_TYPE = 'payment_term';

    /**
     * @var Translator;
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->translator = static::getContainer()->get('translator');
        $currentBundleDataFixturesNameSpace = 'Oro\Bundle\PaymentBundle\Tests\Functional';
        $this->loadFixtures(
            [
                $currentBundleDataFixturesNameSpace.'\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData',
                $currentBundleDataFixturesNameSpace.'\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleDestinationData',
                $currentBundleDataFixturesNameSpace.'\DataFixtures\LoadUserData',
                LoadChannelData::class
            ]
        );
    }

    public function testIndex()
    {
        $auth = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('payment-methods-configs-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Payment Rule')->attr('href');
        static::assertEquals($this->getUrl('oro_payment_methods_configs_rule_create'), $href);

        $response = $this->client->requestGrid([
            'gridName' => 'payment-methods-configs-rule-grid',
            'payment-methods-configs-rule-grid[_sort_by][id]' => 'ASC',
        ]);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');

        $payMethods = $paymentRule->getMethodConfigs();
        $payMethodsLabels = [];
        foreach ($payMethods as $method) {
            $payMethodsLabels[] = $this->translator
                ->trans(sprintf('oro.payment.admin.%s.label', $method->getType()));
        }

        $expectedData = [
            'data' => [
                [
                    'id' => $paymentRule->getId(),
                    'name' => $paymentRule->getRule()->getName(),
                    'enabled' => $paymentRule->getRule()->isEnabled(),
                    'sortOrder' => $paymentRule->getRule()->getSortOrder(),
                    'currency' => $paymentRule->getCurrency(),
                    'expression' => $paymentRule->getRule()->getExpression(),
                    'methodConfigs' => $payMethodsLabels,
                    'destinations' => implode('</br>', $paymentRule->getDestinations()->getValues()),
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

            static::assertEquals($expectedColumns, $testedColumns);
        }

        $expectedDataCount = count($expectedData['data']);
        for ($i = 0; $i < $expectedDataCount; $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                static::assertArrayHasKey($key, $data[$i]);
                switch ($key) {
                    case 'methodConfigs':
                        foreach ($value as $methodLabel) {
                            static::assertContains($methodLabel, $data[$i][$key]);
                        }
                        break;
                    default:
                        static::assertEquals(trim($value), trim($data[$i][$key]));
                }
            }
        }
    }

    public function testIndexWithoutCreate()
    {
        $this->initClient([], static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertEquals(0, $crawler->selectLink('Create Payment Rule')->count());
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
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $name = 'New Rule';

        $formValues = $form->getPhpValues();
        $formValues['oro_payment_methods_configs_rule']['rule']['name'] = $name;
        $formValues['oro_payment_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_payment_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_payment_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_payment_methods_configs_rule']['rule']['expression'] = 1;
        $formValues['oro_payment_methods_configs_rule']['destinations'] = [
            [
                'country' => 'FR',
                'region' => 'FR-75'
            ]
        ];
        $formValues['oro_payment_methods_configs_rule']['methodConfigs'] = [
            [
                'type' => $this->getPaymentMethodIdentifier(),
                'options' => [],
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        static::assertStringContainsString('Payment rule has been saved', $html);
        static::assertStringContainsString('No', $html);

        return $name;
    }

    /**
     * @depends testCreate
     *
     * @param string $name
     */
    public function testView($name)
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($name);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_view', ['id' => $paymentRule->getId()])
        );

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        static::assertStringContainsString($paymentRule->getRule()->getName(), $html);
        $this->checkCurrenciesOnPage($paymentRule->getCurrency(), $html);
        $destination = $paymentRule->getDestinations();
        static::assertStringContainsString((string)$destination[0], $html);
        static::assertStringContainsString($this->getReference('payment_term:channel_1')->getName(), $html);
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
     *
     * @param string $name
     *
     * @return PaymentMethodsConfigsRule|object|null
     */
    public function testUpdate($name)
    {
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($name);

        static::assertNotEmpty($paymentRule);

        $id = $paymentRule->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $id])
        );

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($paymentRule->getCurrency(), $html);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $newName = 'New name for new rule';
        $formValues = $form->getPhpValues();
        $formValues['oro_payment_methods_configs_rule']['rule']['name'] = $newName;
        $formValues['oro_payment_methods_configs_rule']['rule']['enabled'] = false;
        $formValues['oro_payment_methods_configs_rule']['currency'] = 'USD';
        $formValues['oro_payment_methods_configs_rule']['rule']['sortOrder'] = 1;
        $formValues['oro_payment_methods_configs_rule']['destinations'] = [
            [
                'postalCodes' => '54321',
                'country' => 'TH',
                'region' => 'TH-83'
            ]
        ];
        $formValues['oro_payment_methods_configs_rule']['methodConfigs'] = [
            [
                'type' => $this->getPaymentMethodIdentifier(),
                'options' => []
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        static::assertStringContainsString('Payment rule has been saved', $html);

        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($newName);
        static::assertEquals($id, $paymentRule->getId());

        $this->checkCurrency($paymentRule->getCurrency());
        $destination = $paymentRule->getDestinations();
        static::assertEquals('TH', $destination[0]->getCountry()->getIso2Code());
        static::assertEquals('TH-83', $destination[0]->getRegion()->getCombinedCode());
        static::assertEquals('54321', $destination[0]->getPostalCodes()->current()->getName());
        $methodConfigs = $paymentRule->getMethodConfigs();
        static::assertEquals($this->getPaymentMethodIdentifier(), $methodConfigs[0]->getType());

        static::assertFalse($paymentRule->getRule()->isEnabled());

        return $paymentRule;
    }

    /**
     * @depends testUpdate
     */
    public function testCancel(PaymentMethodsConfigsRule $paymentRule)
    {
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($paymentRule->getRule()->getName());

        static::assertNotEmpty($paymentRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($paymentRule->getCurrency(), $html);

        $link = $crawler->selectLink('Cancel')->link();
        $this->client->click($link);
        $response = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        static::assertStringContainsString($paymentRule->getRule()->getName(), $html);
        $this->checkCurrenciesOnPage($paymentRule->getCurrency(), $html);
        $destination = $paymentRule->getDestinations();
        static::assertStringContainsString((string)$destination[0], $html);
        static::assertStringContainsString($this->getReference('payment_term:channel_1')->getName(), $html);
    }

    /**
     * @depends testUpdate
     *
     * @param PaymentMethodsConfigsRule $paymentRule
     *
     * @return object|PaymentMethodsConfigsRule
     */
    public function testUpdateRemoveDestination(PaymentMethodsConfigsRule $paymentRule)
    {
        static::assertNotEmpty($paymentRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        $html = $crawler->html();

        $this->checkCurrenciesOnPage($paymentRule->getCurrency(), $html);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_payment_methods_configs_rule']['destinations'] = [];

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $paymentRule = $this->getEntityManager()->find(
            'OroPaymentBundle:PaymentMethodsConfigsRule',
            $paymentRule->getId()
        );
        static::assertCount(0, $paymentRule->getDestinations());

        return $paymentRule;
    }

    public function testStatusDisableMass()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        /** @var PaymentMethodsConfigsRule $paymentRule1 */
        $paymentRule1 = $this->getReference('payment.payment_methods_configs_rule.1');
        /** @var PaymentMethodsConfigsRule $paymentRule2 */
        $paymentRule2 = $this->getReference('payment.payment_methods_configs_rule.2');
        $url = $this->getUrl(
            'oro_payment_methods_configs_massaction',
            [
                'gridName' => 'payment-methods-configs-rule-grid',
                'actionName' => 'disable',
                'inset' => 1,
                'values' => sprintf(
                    '%s,%s',
                    $paymentRule1->getId(),
                    $paymentRule2->getId()
                )
            ]
        );
        $this->ajaxRequest('POST', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        static::assertTrue($data['successful']);
        static::assertSame(2, $data['count']);
        static::assertFalse(
            $this->getPaymentMethodsConfigsRuleById($paymentRule1->getId())->getRule()->isEnabled()
        );
        static::assertFalse(
            $this->getPaymentMethodsConfigsRuleById($paymentRule2->getId())->getRule()->isEnabled()
        );
    }

    /**
     * @depends testStatusDisableMass
     */
    public function testStatusEnableMass()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        /** @var PaymentMethodsConfigsRule $paymentRule1 */
        $paymentRule1 = $this->getReference('payment.payment_methods_configs_rule.1');
        /** @var PaymentMethodsConfigsRule $paymentRule2 */
        $paymentRule2 = $this->getReference('payment.payment_methods_configs_rule.2');
        $url = $this->getUrl(
            'oro_payment_methods_configs_massaction',
            [
                'gridName' => 'payment-methods-configs-rule-grid',
                'actionName' => 'enable',
                'inset' => 1,
                'values' => sprintf(
                    '%s,%s',
                    $paymentRule1->getId(),
                    $paymentRule2->getId()
                )
            ]
        );
        $this->ajaxRequest('POST', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        static::assertTrue($data['successful']);
        static::assertSame(2, $data['count']);
        static::assertTrue(
            $this->getPaymentMethodsConfigsRuleById($paymentRule1->getId())->getRule()->isEnabled()
        );
        static::assertTrue(
            $this->getPaymentMethodsConfigsRuleById($paymentRule2->getId())->getRule()->isEnabled()
        );
    }

    public function testPaymentMethodsConfigsRuleEditWOPermission()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');

        $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        static::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid([
            'gridName' => 'payment-methods-configs-rule-grid'
        ], [], true);

        $result = static::getJsonResponseContent($response, 200);

        static::assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    /**
     * @return ObjectManager|mixed|null|object
     */
    protected function getEntityManager()
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroPaymentBundle:PaymentMethodsConfigsRule');
    }

    /**
     * @param string $name
     *
     * @return PaymentMethodsConfigsRule|object|null
     */
    protected function getPaymentMethodsConfigsRuleByName($name)
    {
        /** @var RuleInterface $rule */
        $rule = $this->getEntityManager()
            ->getRepository('OroRuleBundle:Rule')
            ->findOneBy(['name' => $name]);

        return $this->getEntityManager()
            ->getRepository('OroPaymentBundle:PaymentMethodsConfigsRule')
            ->findOneBy(['rule' => $rule]);
    }

    /**
     * @param int $id
     *
     * @return PaymentMethodsConfigsRule|null
     */
    protected function getPaymentMethodsConfigsRuleById($id)
    {
        return $this->getEntityManager()
            ->getRepository('OroPaymentBundle:PaymentMethodsConfigsRule')
            ->find($id);
    }

    /**
     * @return string
     */
    protected function getPaymentMethodIdentifier()
    {
        return (new PrefixedIntegrationIdentifierGenerator(self::PAYMENT_METHOD_TYPE))
            ->generateIdentifier($this->getReference('payment_term:channel_1'));
    }
}
