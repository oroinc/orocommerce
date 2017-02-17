<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config\ChannelFactory;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config\PaymentTermConfig;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoadPaymentTermIntegration extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container) {
            return;
        }

        if (!$this->container->hasParameter('oro_integration.entity.class')) {
            return;
        }
        
        $channel = $this->loadIntegration($manager);

        $this->loadPaymentRule($manager, $channel);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Channel
     */
    private function loadIntegration(ObjectManager $manager)
    {
        $label = (new LocalizedFallbackValue())->setString(PaymentTermConfig::PAYMENT_TERM_LABEL);

        $settings = new PaymentTermSettings();
        $settings->addLabel($label)->addShortLabel($label);

        $channel = $this->createChannelFactory($this->container)->createChannel(
            $this->getOrganization($manager),
            $settings,
            true
        );

        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    /**
     * @param ObjectManager $manager
     * @param Channel       $channel
     */
    private function loadPaymentRule(ObjectManager $manager, Channel $channel)
    {
        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($this->getPaymentTermIdentifier($channel));

        $rule = new Rule();
        $rule->setName('Default')
            ->setEnabled(true)
            ->setSortOrder(1);

        $paymentRule = new PaymentMethodsConfigsRule();
        $paymentRule->setRule($rule)
            ->setCurrency('USD')
            ->addMethodConfig($methodConfig);

        $manager->persist($paymentRule);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization|object
     */
    private function getOrganization(ObjectManager $manager)
    {
        if ($this->hasReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)) {
            return $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION);
        } else {
            return $manager
                ->getRepository('OroOrganizationBundle:Organization')
                ->getFirst();
        }
    }

    /**
     * @param Channel $channel
     *
     * @return int|string
     */
    private function getPaymentTermIdentifier(Channel $channel)
    {
        return $this->container
            ->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($channel);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ChannelFactory
     */
    protected function createChannelFactory(ContainerInterface $container)
    {
        return new ChannelFactory(
            $container->get('oro_payment_term.integration.channel'),
            $container->get('translator')
        );
    }
}
