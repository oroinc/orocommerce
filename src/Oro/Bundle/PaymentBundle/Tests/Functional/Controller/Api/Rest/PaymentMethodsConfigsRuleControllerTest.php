<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentMethodsConfigsRuleControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);

        $currentBundleDataFixturesNameSpace = 'Oro\Bundle\PaymentBundle\Tests\Functional';
        $this->loadFixtures(
            [
                $currentBundleDataFixturesNameSpace.'\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData',
                $currentBundleDataFixturesNameSpace.'\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleDestinationData',
                $currentBundleDataFixturesNameSpace.'\DataFixtures\LoadUserData'
            ]
        );
    }

    public function testDisableAction()
    {
        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_disable_paymentmethodsconfigsrules', ['id' => $paymentRule->getId()]),
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(
            false,
            $this->getReference('payment.payment_methods_configs_rule.1')->getRule()->isEnabled()
        );
    }

    /**
     * @depends testDisableAction
     */
    public function testEnableAction()
    {
        /** @var PaymentMethodsConfigsRule $paymentRule */
        $paymentRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_enable_paymentmethodsconfigsrules', ['id' => $paymentRule->getId()]),
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(
            true,
            $this->getReference('payment.payment_methods_configs_rule.1')->getRule()->isEnabled()
        );
    }
}
