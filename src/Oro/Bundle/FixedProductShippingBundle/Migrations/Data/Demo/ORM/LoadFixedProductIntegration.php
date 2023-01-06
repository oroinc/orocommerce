<?php
declare(strict_types=1);

namespace Oro\Bundle\FixedProductShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The fixture created Fixed Product Shipping integration and shipping rules for demo data.
 */
class LoadFixedProductIntegration extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const PRODUCT_ID_THRESHOLD = 21;

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
        $label = (new LocalizedFallbackValue())->setString('Fixed Product Shipping');

        $transport = new FixedProductSettings();
        $transport->addLabel($label);

        $channel = new Channel();
        $channel->setType(FixedProductChannelType::TYPE)
            ->setName('Fixed Product')
            ->setEnabled(true)
            ->setOrganization($this->getOrganization($manager))
            ->setDefaultUserOwner($this->getFirstUser($manager))
            ->setTransport($transport);

        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    protected function loadShippingRule(ObjectManager $manager, Channel $channel): void
    {
        $typeConfig = new ShippingMethodTypeConfig();
        $typeConfig->setEnabled(true);
        $typeConfig->setType(FixedProductMethodType::IDENTIFIER)
            ->setOptions([
                FixedProductMethodType::SURCHARGE_TYPE => FixedProductMethodType::FIXED_AMOUNT,
                FixedProductMethodType::SURCHARGE_AMOUNT => 10,
            ]);

        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod($this->getFixedProductIdentifier($channel))
            ->addTypeConfig($typeConfig);

        $rule = new Rule();
        $rule->setName('Fixed Product Shipping')
            ->setExpression(\sprintf(
                'lineItems.any(lineItem.product.id <= %d)',
                static::PRODUCT_ID_THRESHOLD
            ))
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

    private function getFixedProductIdentifier(Channel $channel): string
    {
        return $this->container
            ->get('oro_fixed_product_shipping.method.identifier_generator.method')
            ->generateIdentifier($channel);
    }

    private function getDefaultCurrency(): string
    {
        /** @var ConfigManager $configManager * */
        $configManager = $this->container->get('oro_config.global');
        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);

        return $configManager->get($currencyConfigKey) ?: CurrencyConfig::DEFAULT_CURRENCY;
    }
}
