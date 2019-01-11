<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadConsentConfigData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    public static $consentReferencesOnEnabling = [
        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
        LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS,
        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
        LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
        LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
        LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $consentConfigItems = array_map(
            function ($consentReference) {
                return new ConsentConfig(
                    $this->getReference($consentReference)
                );
            },
            self::$consentReferencesOnEnabling
        );

        $configItemsOnSave = $this->container
            ->get('oro_consent.system_config.consent_config_converter')
            ->convertBeforeSave(
                $consentConfigItems
            );

        $this->container
            ->get('oro_config.global')
            ->set(
                Configuration::getConfigKey(Configuration::ENABLED_CONSENTS),
                $configItemsOnSave
            );

        $this->container->get('oro_config.global')->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadConsentsData::class
        ];
    }
}
