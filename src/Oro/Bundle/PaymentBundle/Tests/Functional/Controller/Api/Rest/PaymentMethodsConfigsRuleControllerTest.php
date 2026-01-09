<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentMethodsConfigsRuleControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);

        $testNamespace = 'Oro\Bundle\PaymentBundle\Tests\Functional';
        $this->loadFixtures(
            [
                $testNamespace . '\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData',
                $testNamespace . '\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleDestinationData',
                $testNamespace . '\DataFixtures\LoadUserData'
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
            static::generateApiAuthHeader(LoadUserData::USER_EDITOR)
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
            static::generateApiAuthHeader(LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(
            true,
            $this->getReference('payment.payment_methods_configs_rule.1')->getRule()->isEnabled()
        );
    }
}
