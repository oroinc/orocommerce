<?php

namespace Oro\Bundle\PaymentBundle\Tests\Behat\Context;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

class FeatureContext extends OroFeatureContext
{
    use UserUtilityTrait;

    /**
     * @Given /^(?:I )?create payment rule with "(?P<paymentMethodName>(?:[^"]+))" payment method$/
     */
    public function iCreatePaymentMethodsConfigsRule(string $paymentMethodName)
    {
        $paymentMethodIdentifier = $this->getPaymentMethodIdentifier($paymentMethodName);

        $currency = $this->getAppContainer()->get('oro_currency.config.currency')->getDefaultCurrency();

        $rule = (new Rule())
            ->setSortOrder(1)
            ->setName(sprintf('%sPaymentRule', $paymentMethodName))
            ->setEnabled(true);

        $paymentMethodConfig = (new PaymentMethodConfig())->setType($paymentMethodIdentifier);

        $doctrineHelper = $this->getAppContainer()->get('oro_entity.doctrine_helper');

        $organization = $this->getFirstUser($doctrineHelper->getEntityManagerForClass(User::class))
            ->getOrganization();

        $paymentMethodsConfigsRule = (new PaymentMethodsConfigsRule())
            ->setRule($rule)
            ->setCurrency($currency)
            ->addMethodConfig($paymentMethodConfig)
            ->setOrganization($organization);

        $entityManager = $doctrineHelper->getEntityManagerForClass(PaymentMethodsConfigsRule::class);
        $entityManager->persist($paymentMethodsConfigsRule);
        $entityManager->flush();
    }

    private function getPaymentMethodIdentifier(string $paymentMethodName): string
    {
        $paymentMethodProvider = $this->getAppContainer()->get('oro_payment.payment_method.composite_provider');
        $paymentMethodViewProvider = $this->getAppContainer()
            ->get('oro_payment.payment_method_view.composite_provider');
        foreach ($paymentMethodProvider->getPaymentMethods() as $identifier => $paymentMethod) {
            $paymentMethodView = $paymentMethodViewProvider->getPaymentMethodView($identifier);
            if (!$paymentMethodView) {
                continue;
            }

            if ($paymentMethodView->getAdminLabel() === $paymentMethodName) {
                return $identifier;
            }
        }

        self::fail(sprintf('Payment method with name %s was not found', $paymentMethodName));
    }
}
