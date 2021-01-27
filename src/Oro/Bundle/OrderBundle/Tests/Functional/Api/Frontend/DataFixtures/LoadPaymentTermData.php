<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads the payment rule for "payment term" payment.
 */
class LoadPaymentTermData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadUser::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $channel = $this->loadIntegration($manager);

        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($this->getPaymentTermIdentifier($channel));

        $rule = new Rule();
        $rule
            ->setName('Default')
            ->setEnabled(true)
            ->setSortOrder(1);

        $ruleConfig = new PaymentMethodsConfigsRule();
        $ruleConfig
            ->setRule($rule)
            ->setOrganization($this->getReference('organization'))
            ->setCurrency('USD')
            ->addMethodConfig($methodConfig);

        $this->setReference('payment_term_method_config', $methodConfig);
        $this->setReference('payment_term_rule', $rule);

        $manager->persist($ruleConfig);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Channel
     */
    private function loadIntegration(ObjectManager $manager)
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Payment Term');

        $transport = new PaymentTermSettings();
        $transport->addLabel($label);
        $transport->addShortLabel($label);

        $channel = new Channel();
        $channel
            ->setType(PaymentTermChannelType::TYPE)
            ->setName((string)$label)
            ->setEnabled(true)
            ->setOrganization($this->getReference('organization'))
            ->setDefaultUserOwner($this->getReference('user'))
            ->setTransport($transport);

        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getPaymentTermIdentifier(Channel $channel)
    {
        return $this->container
            ->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($channel);
    }
}
