<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleDestinationData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @group CommunityEdition
 */
class PaymentMethodsConfigsRuleControllerTest extends WebTestCase
{
    private const PAYMENT_METHOD_TYPE = 'payment_term';

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPaymentMethodsConfigsRuleData::class,
            LoadPaymentMethodsConfigsRuleDestinationData::class,
            LoadUserData::class,
            LoadChannelData::class
        ]);
    }

    public function testIndex()
    {
        $auth = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('payment-methods-configs-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Payment Rule')->attr('href');
        self::assertEquals($this->getUrl('oro_payment_methods_configs_rule_create'), $href);

        $response = $this->client->requestGrid([
            'gridName' => 'payment-methods-configs-rule-grid',
            'payment-methods-configs-rule-grid[_sort_by][id]' => 'ASC',
        ]);

        $result = self::getJsonResponseContent($response, 200);

        $data = $result['data'];

        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');

        $payMethods = $paymentRule->getMethodConfigs();
        $payMethodsLabels = [];
        foreach ($payMethods as $method) {
            $payMethodsLabels[] = self::getContainer()->get('translator')
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

            self::assertEquals($expectedColumns, $testedColumns);
        }

        $expectedDataCount = count($expectedData['data']);
        for ($i = 0; $i < $expectedDataCount; $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                self::assertArrayHasKey($key, $data[$i]);
                if ('methodConfigs' === $key) {
                    foreach ($value as $methodLabel) {
                        self::assertContains($methodLabel, $data[$i][$key]);
                    }
                } else {
                    self::assertEquals(trim($value), trim($data[$i][$key]));
                }
            }
        }
    }

    public function testIndexWithoutCreate()
    {
        $this->initClient([], self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertEquals(0, $crawler->selectLink('Create Payment Rule')->count());
    }

    public function testCreate(): string
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_methods_configs_rule_create'));

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

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();

        self::assertStringContainsString('Payment rule has been saved', $html);
        self::assertStringContainsString('No', $html);

        return $name;
    }

    /**
     * @depends testCreate
     */
    public function testView(string $name)
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($name);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_view', ['id' => $paymentRule->getId()])
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        self::assertStringContainsString($paymentRule->getRule()->getName(), $html);
        $destination = $paymentRule->getDestinations();
        self::assertStringContainsString((string)$destination[0], $html);
        self::assertStringContainsString($this->getReference('payment_term:channel_1')->getName(), $html);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(string $name): PaymentMethodsConfigsRule
    {
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($name);

        self::assertNotEmpty($paymentRule);

        $id = $paymentRule->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $id])
        );

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

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        self::assertStringContainsString('Payment rule has been saved', $html);

        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($newName);
        self::assertEquals($id, $paymentRule->getId());

        $destination = $paymentRule->getDestinations();
        self::assertEquals('TH', $destination[0]->getCountry()->getIso2Code());
        self::assertEquals('TH-83', $destination[0]->getRegion()->getCombinedCode());
        self::assertEquals('54321', $destination[0]->getPostalCodes()->current()->getName());
        $methodConfigs = $paymentRule->getMethodConfigs();
        self::assertEquals($this->getPaymentMethodIdentifier(), $methodConfigs[0]->getType());

        self::assertFalse($paymentRule->getRule()->isEnabled());

        return $paymentRule;
    }

    /**
     * @depends testUpdate
     */
    public function testCancel(PaymentMethodsConfigsRule $paymentRule)
    {
        $paymentRule = $this->getPaymentMethodsConfigsRuleByName($paymentRule->getRule()->getName());

        self::assertNotEmpty($paymentRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        $link = $crawler->selectLink('Cancel')->link();
        $this->client->click($link);
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        self::assertStringContainsString($paymentRule->getRule()->getName(), $html);
        $destination = $paymentRule->getDestinations();
        self::assertStringContainsString((string)$destination[0], $html);
        self::assertStringContainsString($this->getReference('payment_term:channel_1')->getName(), $html);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateRemoveDestination(PaymentMethodsConfigsRule $paymentRule): PaymentMethodsConfigsRule
    {
        self::assertNotEmpty($paymentRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_payment_methods_configs_rule']['destinations'] = [];

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $paymentRule = $this->getEntityManager()->find(PaymentMethodsConfigsRule::class, $paymentRule->getId());
        self::assertCount(0, $paymentRule->getDestinations());

        return $paymentRule;
    }

    public function testStatusDisableMass()
    {
        $this->initClient([], self::generateBasicAuthHeader());
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
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['successful']);
        self::assertSame(2, $data['count']);
        self::assertFalse(
            $this->getPaymentMethodsConfigsRuleById($paymentRule1->getId())->getRule()->isEnabled()
        );
        self::assertFalse(
            $this->getPaymentMethodsConfigsRuleById($paymentRule2->getId())->getRule()->isEnabled()
        );
    }

    /**
     * @depends testStatusDisableMass
     */
    public function testStatusEnableMass()
    {
        $this->initClient([], self::generateBasicAuthHeader());
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
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['successful']);
        self::assertSame(2, $data['count']);
        self::assertTrue(
            $this->getPaymentMethodsConfigsRuleById($paymentRule1->getId())->getRule()->isEnabled()
        );
        self::assertTrue(
            $this->getPaymentMethodsConfigsRuleById($paymentRule2->getId())->getRule()->isEnabled()
        );
    }

    public function testPaymentMethodsConfigsRuleEditWOPermission()
    {
        $authParams = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');

        $this->client->request(
            'GET',
            $this->getUrl('oro_payment_methods_configs_rule_update', ['id' => $paymentRule->getId()])
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = self::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid([
            'gridName' => 'payment-methods-configs-rule-grid'
        ], [], true);

        $result = self::getJsonResponseContent($response, 200);

        self::assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(PaymentMethodsConfigsRule::class);
    }

    private function getPaymentMethodsConfigsRuleByName(string $name): ?PaymentMethodsConfigsRule
    {
        $em = $this->getEntityManager();

        return $em->getRepository(PaymentMethodsConfigsRule::class)
            ->findOneBy(['rule' => $em->getRepository(Rule::class)->findOneBy(['name' => $name])]);
    }

    private function getPaymentMethodsConfigsRuleById(int $id): PaymentMethodsConfigsRule
    {
        return $this->getEntityManager()
            ->getRepository(PaymentMethodsConfigsRule::class)
            ->find($id);
    }

    private function getPaymentMethodIdentifier(): string
    {
        return (new PrefixedIntegrationIdentifierGenerator(self::PAYMENT_METHOD_TYPE))
            ->generateIdentifier($this->getReference('payment_term:channel_1'));
    }
}
