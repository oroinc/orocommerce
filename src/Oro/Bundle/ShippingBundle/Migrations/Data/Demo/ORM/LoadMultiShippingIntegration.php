<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Integration\MultiShippingChannelType;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads Multi Shipping shipping rule
 */
class LoadMultiShippingIntegration extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    private ?ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadAdminUserData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        if (!$this->container) {
            return;
        }

        $channel = $this->loadIntegration($manager);
        $this->loadShippingRule($manager, $channel);
    }

    protected function loadIntegration(ObjectManager $manager): Channel
    {
        $transport = new MultiShippingSettings();

        $channel = new Channel();
        $channel->setType(MultiShippingChannelType::TYPE)
            ->setName('Multi Shipping')
            ->setEnabled(true)
            ->setOrganization($this->getOrganization($manager))
            ->setDefaultUserOwner($this->getMainUser($manager))
            ->setTransport($transport);

        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    protected function loadShippingRule(ObjectManager $manager, Channel $channel): void
    {
        $typeConfig = new ShippingMethodTypeConfig();
        $typeConfig->setEnabled(true);
        $typeConfig->setType(MultiShippingMethodType::IDENTIFIER)
            ->setOptions([]);

        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod($this->getMultiShippingIdentifier($channel))
            ->addTypeConfig($typeConfig);

        $rule = new Rule();
        $rule->setName('Multi Shipping')
            ->setExpression('')
            ->setEnabled(true)
            ->setSortOrder(1);

        $shippingRule = new ShippingMethodsConfigsRule();

        $shippingRule->setRule($rule)
            ->setOrganization($this->getOrganization($manager))
            ->setCurrency($this->getDefaultCurrency())
            ->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }

    private function getOrganization(ObjectManager $manager): Organization
    {
        if ($this->hasReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)) {
            return $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION);
        }

        return $manager->getRepository(Organization::class)->getFirst();
    }

    /**
     * @param ObjectManager $manager
     * @return User
     * @throws EntityNotFoundException
     */
    public function getMainUser(ObjectManager $manager): User
    {
        /** @var User $entity */
        $entity = $manager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
        if (!$entity) {
            throw new EntityNotFoundException('Main user does not exist.');
        }

        return $entity;
    }

    private function getMultiShippingIdentifier(Channel $channel): string
    {
        return $this->container
            ->get('oro_shipping.multi_shipping.method.identifier_generator.method')
            ->generateIdentifier($channel);
    }

    private function getDefaultCurrency(): string
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);

        return $configManager->get($currencyConfigKey) ?: CurrencyConfig::DEFAULT_CURRENCY;
    }
}
