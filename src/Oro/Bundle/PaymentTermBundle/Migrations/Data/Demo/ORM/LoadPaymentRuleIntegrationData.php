<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads payment term integration channel.
 */
class LoadPaymentRuleIntegrationData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const PAYMENT_TERM_INTEGRATION_CHANNEL_REFERENCE = 'payment_term_integration_channel';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadShippingRule($manager, $this->loadIntegration($manager));
    }

    private function loadIntegration(ObjectManager $manager): Channel
    {
        $label = (new LocalizedFallbackValue())->setString('Payment Term');

        $transport = new PaymentTermSettings();
        $transport->addLabel($label);
        $transport->addShortLabel($label);

        $channel = new Channel();
        $channel->setType(PaymentTermChannelType::TYPE);
        $channel->setName(sprintf('%s %s', $label->getString(), $this->getDefaultCurrency()));
        $channel->setEnabled(true);
        $channel->setOrganization($this->getOrganization($manager));
        $channel->setDefaultUserOwner($this->getMainUser($manager));
        $channel->setTransport($transport);

        $this->setReference(self::PAYMENT_TERM_INTEGRATION_CHANNEL_REFERENCE, $channel);

        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    private function loadShippingRule(ObjectManager $manager, Channel $channel): void
    {
        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($this->getPaymentTermIdentifier($channel));

        $rule = new Rule();
        $rule->setName('Default');
        $rule->setEnabled(true);
        $rule->setSortOrder(1);

        $shippingRule = new PaymentMethodsConfigsRule();
        $shippingRule->setRule($rule);
        $shippingRule->setOrganization($this->getOrganization($manager));
        $shippingRule->setCurrency($this->getDefaultCurrency());
        $shippingRule->addMethodConfig($methodConfig);

        $manager->persist($shippingRule);
        $manager->flush();
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        if ($this->hasReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)) {
            return $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION);
        }

        return $manager->getRepository(Organization::class)->getFirst();
    }

    public function getMainUser(ObjectManager $manager): User
    {
        $entity = $manager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
        if (!$entity) {
            throw new EntityNotFoundException('Main user does not exist.');
        }

        return $entity;
    }

    private function getPaymentTermIdentifier(Channel $channel): string
    {
        return $this->container->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($channel);
    }

    protected function getDefaultCurrency(): string
    {
        /** @var ConfigManager $configManager * */
        $configManager = $this->container->get('oro_config.global');

        return $configManager->get(CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY))
            ?: CurrencyConfig::DEFAULT_CURRENCY;
    }
}
