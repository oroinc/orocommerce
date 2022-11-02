<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShippingRuleControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadShippingMethodsConfigsRulesWithConfigs::class,
            LoadUserData::class,
        ]);
    }

    public function testDisableAction()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_disable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(false, $this->getReference('shipping_rule.1')->getRule()->isEnabled());
    }

    public function testEnableAction()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.3');
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_enable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(true, $this->getReference('shipping_rule.3')->getRule()->isEnabled());
    }
}
