<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData as
    BasicLoadPaymentMethodsConfigsRuleData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPaymentMethodsConfigsRuleData extends BasicLoadPaymentMethodsConfigsRuleData implements
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        /** @var Channel $channel */
        $channel = $this->getReference('apruve:channel_1');
        $methodConfig->setType($this->getPaymentMethodIdentifier($channel));

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
        return array_merge(parent::getDependencies(), [LoadApruveChannelData::class]);
    }

    /**
     * @param Channel $channel
     * @return string
     */
    protected function getPaymentMethodIdentifier(Channel $channel)
    {
        return $this->container->get('oro_apruve.method.generator.identifier')
            ->generateIdentifier($channel);
    }
}
