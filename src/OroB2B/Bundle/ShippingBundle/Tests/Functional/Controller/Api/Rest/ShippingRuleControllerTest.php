<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class ShippingRuleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules',
                'OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    public function testDisableAction()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_disable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
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
            $this->getUrl('orob2b_api_enable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
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
            $this->getUrl('orob2b_api_delete_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader()
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    public function testDeleteWOPermission()
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }
}
