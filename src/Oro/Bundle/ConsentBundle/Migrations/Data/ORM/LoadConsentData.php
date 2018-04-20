<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\OroConsentExtension;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM\LoadWebCatalogDemoData;

/**
 * Enables Consents Feature
 * Creates two nodes with landing pages and assigns it to Consents
 * Enables created consents
 */
class LoadConsentData extends LoadWebCatalogDemoData
{
    const NODE1_REFERENCE_NAME = 'terms-and-conditions-node';
    const NODE2_REFERENCE_NAME = 'email-subscription-node';
    const MANDATORY_CONSENT = 'Terms and Conditions';
    const OPTIONAL_CONSENT = 'Email subscription';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWebCatalogDemoData::class];
    }

    private function enableConsentFeature()
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(OroConsentExtension::ALIAS . '.consent_feature_enabled', true);

        $configManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return WebCatalog
     */
    private function getWebCatalog(ObjectManager $manager)
    {
        return $manager->getRepository(WebCatalog::class)
            ->findOneBy(['name' => LoadWebCatalogDemoData::DEFAULT_WEB_CATALOG_NAME]);
    }

    /**
     * @param string $title
     * @param bool $isMandatory
     * @param string $nodeReferenceName
     * @param ObjectManager $manager
     * @return Consent
     */
    private function createConsent($title, $isMandatory, $nodeReferenceName, ObjectManager $manager)
    {
        $defaultName = new LocalizedFallbackValue();
        $defaultName->setString($title);
        $node = $this->getReference($nodeReferenceName);
        $consent = (new Consent())
            ->setDeclinedNotification(true)
            ->setMandatory($isMandatory)
            ->setContentNode($node)
            ->setDefaultName($defaultName);

        $manager->persist($consent);
        $manager->flush();

        return $consent;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->enableConsentFeature();
        $webCatalog = $this->getWebCatalog($manager);
        $nodesData =
            $this->getWebCatalogData('@OroConsentBundle/Migrations/Data/Demo/ORM/data/consent_nodes.yml');

        $this->loadContentNodes($manager, $webCatalog, $nodesData);
        $this->createConsent(self::MANDATORY_CONSENT, true, self::NODE1_REFERENCE_NAME, $manager);
        $this->createConsent(self::OPTIONAL_CONSENT, false, self::NODE2_REFERENCE_NAME, $manager);
    }
}
