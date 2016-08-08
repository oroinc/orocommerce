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
        $this->initClient([], $this->generateBasicAuthHeader());
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
        return $name;
    }

    /**
     * @depends testCreate
     * @param string $name
     */
    public function testView($name)
    {
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
     * @return int
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
        //$this->assertContains($configurations[0]->getType(), $html);
        $this->assertContains($configurations[0]->getMethod(), $html);

        return $id;
    }

    public function testStatusEnalbeMass()
    {
        $url = $this->getUrl(
            'orob2b_status_shipping_rule_massaction',
            [
                'gridName' => 'shipping-rule-grid',
                'actionName' => 'enable',
                'inset' => 1,
                'values' => $this->getReference('shipping_rule.1')->getId()
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
        $url = $this->getUrl(
            'orob2b_status_shipping_rule_massaction',
            [
                'gridName' => 'shipping-rule-grid',
                'actionName' => 'disable',
                'inset' => 1,
                'values' => $this->getReference('shipping_rule.1')->getId()
            ]
        );
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 1);
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
