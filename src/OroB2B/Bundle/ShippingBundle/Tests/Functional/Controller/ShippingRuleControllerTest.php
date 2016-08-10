<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

/**
 * @dbIsolation
 */
class ShippingRuleControllerTest extends WebTestCase
{
    const NAME = 'New rule';

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadShippingRules::class, LoadUserData::class]);
    }

    public function testIndex()
    {
        $auth = $this->generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR);
        $this->initClient([], $auth);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shipping_rule_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('shipping-rule-grid', $crawler->html());
        $href = $crawler->selectLink('Create Shipping Rule')->attr('href');
        $this->assertEquals($this->getUrl('orob2b_shipping_rule_create'), $href);

        $response = $this->client->requestGrid([
            'gridName'                         => 'shipping-rule-grid',
            'shipping-rule-grid[_sort_by][id]' => 'ASC',
        ]);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $expectedData = [
            'data'    => [
                [
                    'id'             => $shippingRule->getId(),
                    'name'           => $shippingRule->getName(),
                    'enabled'        => $shippingRule->isEnabled(),
                    'priority'       => $shippingRule->getPriority(),
                    'currency'       => $shippingRule->getCurrency(),
                    'conditions'     => $shippingRule->getConditions(),
                    'configurations' => implode('</br>', $shippingRule->getConfigurations()->getValues()),
                    'destinations'   => implode('</br>', $shippingRule->getDestinations()->getValues()),
                ],
            ],
            'columns' => [
                'id',
                'name',
                'enabled',
                'priority',
                'currency',
                'conditions',
                'configurations',
                'destinations',
                'delete_link',
                'disable_link',
                'enable_link',
                'update_link',
                'view_link',
            ],
        ];

        $this->assertEquals(count($expectedData['data']), count($data));

        if (isset($expectedData['columns'])) {
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            $this->assertEquals($expectedColumns, $testedColumns);
        }

        for ($i = 0; $i < count($expectedData['data']); $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                $this->assertArrayHasKey($key, $data[$i]);
                $this->assertEquals($value, $data[$i][$key]);
            }
        }

    }

    public function testIndexWithoutCreate()
    {
        $this->initClient([], $this->generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER));
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shipping_rule_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals(0, $crawler->selectLink('Create Shipping Rule')->count());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::USER_VIEWER_CREATOR, LoadUserData::USER_VIEWER_CREATOR)
        );
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shipping_rule_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $name = 'New Rule';

        $formValues = $form->getPhpValues();
        $formValues['orob2b_shipping_rule']['name'] = $name;
        $formValues['orob2b_shipping_rule']['enabled'] = false;
        $formValues['orob2b_shipping_rule']['currency'] = 'USD';
        $formValues['orob2b_shipping_rule']['priority'] = 1;
        $formValues['orob2b_shipping_rule']['destinations'] = [
            [
                'postalCode' => '54321',
                'country' => 'FR',
                'region' => 'FR-75'
            ]
        ];
        $formValues['orob2b_shipping_rule']['configurations'] = [
            [
                'enabled' => true,
                'method' => FlatRateShippingMethod::NAME,
                'type' => FlatRateShippingMethod::NAME,
                'value' => 12,
                'processingType' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER,
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

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
        $this->initClient([], $this->generateBasicAuthHeader());
        $shippingRule = $this->getShippingRuleByName($name);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_view', ['id' => $shippingRule->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        $this->assertContains($shippingRule->getName(), $html);
        $this->assertContains($shippingRule->getCurrency(), $html);
        $destination = $shippingRule->getDestinations();
        $this->assertContains((string)$destination[0], $html);
        $configurations = $shippingRule->getConfigurations();
        $this->assertContains($configurations[0]->getType(), $html);
        $this->assertContains($configurations[0]->getMethod(), $html);
    }

    /**
     * @depends testCreate
     * @param string $name
     * @return ShippingRule|object|null
     */
    public function testUpdate($name)
    {
        $shippingRule = $this->getShippingRuleByName($name);

        $this->assertNotEmpty($shippingRule);

        $id = $shippingRule->getId();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_update', ['id' => $shippingRule->getId()])
        );

        $html = $crawler->html();

        $this->assertContains($shippingRule->getCurrency(), $html);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $newName = 'New name for new rule';
        $formValues = $form->getPhpValues();
        $formValues['orob2b_shipping_rule']['name'] = $newName;
        $formValues['orob2b_shipping_rule']['enabled'] = true;
        $formValues['orob2b_shipping_rule']['currency'] = 'USD';
        $formValues['orob2b_shipping_rule']['priority'] = 1;
        $formValues['orob2b_shipping_rule']['destinations'] = [
            [
                'postalCode' => '54321',
                'country' => 'TH',
                'region' => 'TH-83'
            ]
        ];
        $formValues['orob2b_shipping_rule']['configurations'] = [
            [
                'enabled' => true,
                'method' => FlatRateShippingMethod::NAME,
                'type' => FlatRateShippingMethod::NAME,
                'value' => 12,
                'processingType' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER,
                'currency' => 'USD',
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $html = $crawler->html();
        $this->assertContains('Shipping rule has been saved', $html);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_view', ['id' => $shippingRule->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $shippingRule = $this->getShippingRuleByName($newName);

        $html = $crawler->html();
        $this->assertContains($shippingRule->getName(), $html);
        $this->assertContains($shippingRule->getCurrency(), $html);
        $destination = $shippingRule->getDestinations();
        $this->assertContains((string)$destination[0], $html);
        $configurations = $shippingRule->getConfigurations();
        $this->assertContains($configurations[0]->getMethod(), $html);
        $this->assertContains('Yes', $html);

        return $shippingRule;
    }


    /**
     * @depends testUpdate
     * @param ShippingRule|object|null $shippingRule
     */
    public function testCancel($shippingRule)
    {
        $shippingRule = $this->getShippingRuleByName($shippingRule->getName());

        $this->assertNotEmpty($shippingRule);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_update', ['id' => $shippingRule->getId()])
        );

        $html = $crawler->html();

        $this->assertContains($shippingRule->getCurrency(), $html);

        $link = $crawler->selectLink('Cancel')->link();
        $this->client->click($link);
        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $html = $response->getContent();

        $this->assertContains($shippingRule->getName(), $html);
        $this->assertContains($shippingRule->getCurrency(), $html);
        $destination = $shippingRule->getDestinations();
        $this->assertContains((string)$destination[0], $html);
        $configurations = $shippingRule->getConfigurations();
        $this->assertContains($configurations[0]->getMethod(), $html);
    }

    public function testStatusEnableMass()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $url = $this->getUrl(
            'orob2b_status_shipping_rule_massaction',
            [
                'gridName'   => 'shipping-rule-grid',
                'actionName' => 'enable',
                'inset'      => 1,
                'values'     => $this->getReference('shipping_rule.1')->getId()
            ]
        );
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 1);
    }

    public function testStatusDisableMass()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $url = $this->getUrl(
            'orob2b_status_shipping_rule_massaction',
            [
                'gridName'   => 'shipping-rule-grid',
                'actionName' => 'disable',
                'inset'      => 1,
                'values'     => $this->getReference('shipping_rule.1')->getId()
            ]
        );
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 1);
    }


    public function testShippingRuleEditWOPermission()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_update', ['id' => $shippingRule->getId()])
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testShippingRuleEdit()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR);
        $this->initClient([], $authParams);

        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shipping_rule_update', ['id' => $shippingRule->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $form['orob2b_shipping_rule[enabled]'] = !$shippingRule->isEnabled();
        $form['orob2b_shipping_rule[name]'] = $shippingRule->getName() . ' new name';
        $form['orob2b_shipping_rule[priority]'] = $shippingRule->getPriority() + 1;
        $form['orob2b_shipping_rule[currency]'] = $shippingRule->getCurrency() === 'USD' ? 'EUR' : 'USD';
        $form['orob2b_shipping_rule[stopProcessing]'] = !$shippingRule->isStopProcessing();
        $form['orob2b_shipping_rule[conditions]'] = $shippingRule->getConditions() . ' new data';
        $form['orob2b_shipping_rule[destinations][0][postalCode]'] = '11111';
        $form['orob2b_shipping_rule[configurations][0][enabled]'] = '1';
        $form['orob2b_shipping_rule[configurations][0][currency]'] = 'USD';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Shipping rule has been saved', $crawler->html());

    }

    public function testDeleteButtonNotVisible()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid([
            'gridName' => 'shipping-rule-grid'
        ], [], true);

        $result = static::getJsonResponseContent($response, 200);

        $this->assertEquals(false, isset($result['metadata']['massActions']['delete']));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|mixed|null|object
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BShippingBundle:ShippingRule');
    }

    /**
     * @param string $name
     * @return ShippingRule|object|null
     */
    protected function getShippingRuleByName($name)
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BShippingBundle:ShippingRule')
            ->findOneBy(['name' => $name]);
    }
}
