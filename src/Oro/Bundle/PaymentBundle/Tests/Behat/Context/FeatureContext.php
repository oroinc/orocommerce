<?php

namespace Oro\Bundle\PaymentBundle\Tests\Behat\Context;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

class FeatureContext extends OroFeatureContext
{
    use UserUtilityTrait;

    private CurrencyProviderInterface $currencyProvider;

    private DoctrineHelper $doctrineHelper;

    private PaymentMethodProviderInterface $paymentMethodProvider;

    private PaymentMethodViewProviderInterface $paymentMethodViewProvider;

    public function __construct(
        CurrencyProviderInterface $currencyProvider,
        DoctrineHelper $doctrineHelper,
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentMethodViewProviderInterface $paymentMethodViewProvider
    ) {
        $this->currencyProvider = $currencyProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
    }

    /**
     * @Given /^(?:I )?create payment rule with "(?P<paymentMethodName>(?:[^"]+))" payment method$/
     */
    public function iCreatePaymentMethodsConfigsRule(string $paymentMethodName)
    {
        $paymentMethodIdentifier = $this->getPaymentMethodIdentifier($paymentMethodName);

        $currency = $this->currencyProvider->getDefaultCurrency();

        $rule = (new Rule())
            ->setSortOrder(1)
            ->setName(sprintf('%sPaymentRule', $paymentMethodName))
            ->setEnabled(true);

        $paymentMethodConfig = (new PaymentMethodConfig())->setType($paymentMethodIdentifier);

        $organization = $this->getFirstUser($this->doctrineHelper->getEntityManagerForClass(User::class))
            ->getOrganization();

        $paymentMethodsConfigsRule = (new PaymentMethodsConfigsRule())
            ->setRule($rule)
            ->setCurrency($currency)
            ->addMethodConfig($paymentMethodConfig)
            ->setOrganization($organization);

        $entityManager = $this->doctrineHelper->getEntityManagerForClass(PaymentMethodsConfigsRule::class);
        $entityManager->persist($paymentMethodsConfigsRule);
        $entityManager->flush();
    }

    private function getPaymentMethodIdentifier(string $paymentMethodName): string
    {
        foreach ($this->paymentMethodProvider->getPaymentMethods() as $identifier => $paymentMethod) {
            $paymentMethodView = $this->paymentMethodViewProvider->getPaymentMethodView($identifier);
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
