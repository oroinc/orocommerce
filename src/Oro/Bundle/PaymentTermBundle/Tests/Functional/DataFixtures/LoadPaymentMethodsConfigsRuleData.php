<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData
    as BasicLoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;

class LoadPaymentMethodsConfigsRuleData extends BasicLoadPaymentMethodsConfigsRuleData
{
    /**
     * @param Channel $channel
     *
     * @return string
     */
    public static function getPaymentMethodIdentifier(Channel $channel)
    {
        return (new PrefixedIntegrationIdentifierGenerator(PaymentTermChannelType::TYPE))
            ->generateIdentifier($channel);
    }
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        /** @var Channel $channel */
        $channel = $this->getReference('payment_term:channel_1');
        $methodConfig->setType(self::getPaymentMethodIdentifier($channel));

        /** @var PaymentMethodsConfigsRule $methodsConfigsRule */
        $methodsConfigsRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $methodsConfigsRule->addMethodConfig($methodConfig);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            ['Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData']
        );
    }
}
