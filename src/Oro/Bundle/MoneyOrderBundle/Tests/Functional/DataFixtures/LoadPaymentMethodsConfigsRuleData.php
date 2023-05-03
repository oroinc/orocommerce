<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData as BaseFixture;

class LoadPaymentMethodsConfigsRuleData extends BaseFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        /** @var Channel $channel */
        $channel = $this->getReference('money_order:channel_1');
        $methodConfig->setType($this->getPaymentMethodIdentifier($channel));

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
        return array_merge(parent::getDependencies(), [LoadMoneyOrderChannelData::class]);
    }

    private function getPaymentMethodIdentifier(Channel $channel): string
    {
        return $this->container->get('oro_money_order.generator.money_order_config_identifier')
            ->generateIdentifier($channel);
    }
}
