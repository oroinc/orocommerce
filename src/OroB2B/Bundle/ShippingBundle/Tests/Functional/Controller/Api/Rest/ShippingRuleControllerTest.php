<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

/**
 * @dbIsolation
 */
class ShippingRuleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules']);
    }

    public function testDisableAction()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_disable_shippingrules', ['id' => $shippingRule->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(false, $this->getReference('shipping_rule.1')->isEnabled());
    }

    /**
     * @depends testDisableAction
     */
    public function testEnableAction()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_enable_shippingrules', ['id' => $shippingRule->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(true, $this->getReference('shipping_rule.1')->isEnabled());
    }

    public function testDelete()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_shippingrules', ['id' => $shippingRule->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
