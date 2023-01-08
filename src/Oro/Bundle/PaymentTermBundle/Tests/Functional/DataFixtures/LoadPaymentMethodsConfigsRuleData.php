<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData as BaseFixture;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;

class LoadPaymentMethodsConfigsRuleData extends BaseFixture
{
    use EnabledPaymentMethodIdentifierTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($this->getPaymentMethodIdentifier($this->container));

        /** @var PaymentMethodsConfigsRule $methodsConfigsRule */
        $methodsConfigsRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $methodsConfigsRule->addMethodConfig($methodConfig);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [LoadChannelData::class]
        );
    }
}
