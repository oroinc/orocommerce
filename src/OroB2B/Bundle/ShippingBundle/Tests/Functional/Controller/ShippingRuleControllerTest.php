<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

/**
 * @dbIsolation
 */
class ShippingRuleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shipping_rule_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('shipping-rule-grid', $crawler->html());
    }

    public function testView()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');

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
        $this->assertContains($destination[0]->__toString(), $html);
        $configurations = $shippingRule->getConfigurations();
        //$this->assertContains($configurations[0]->getType(), $html);
        $this->assertContains($configurations[0]->getMethod(), $html);
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
}
