<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\DependencyInjection\OroConsentExtension;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM\AbstractLoadWebCatalogDemoData;
use Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM\LoadWebCatalogDemoData;

/**
 * Creates two nodes with landing pages and assigns it to Consents
 * Enables created consents
 */
class LoadConsentDemoData extends AbstractLoadWebCatalogDemoData implements DependentFixtureInterface
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
        return [
            EnableConsentFeature::class,
            LoadWebCatalogDemoData::class,
            LoadConsentCmsPagesDemoData::class
        ];
    }

    /**
     * @param Consent[] $consents
     */
    private function enableUserConsents(array $consents)
    {
        $consentConfigs = [];
        foreach ($consents as $consent) {
            $consentConfigs[] =  new ConsentConfig($consent, $consent->getId());
        }
        $consentConfigConverter = $this->container->get('oro_consent.system_config.consent_config_converter');
        $configArray = $consentConfigConverter->convertBeforeSave($consentConfigs);

        $configManager = $this->container->get('oro_config.global');
        $configManager->set(OroConsentExtension::ALIAS . '.' . Configuration::ENABLED_CONSENTS, $configArray);

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

        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();

        $consent = (new Consent())
            ->setDeclinedNotification(true)
            ->setMandatory($isMandatory)
            ->setContentNode($node)
            ->setOwner($user)
            ->setOrganization($organization)
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
        $webCatalog = $this->getWebCatalog($manager);
        $nodesData = $this->getWebCatalogData(__DIR__ . '/data/consent_nodes.yml');

        /** @var ContentNodeRepository $contentNodeRepo */
        $contentNodeRepo = $manager->getRepository(ContentNode::class);

        //Gets root node to assign new nodes to it
        $parentNode = $contentNodeRepo->getRootNodeByWebCatalog($webCatalog);

        $this->loadContentNodes($manager, $webCatalog, $nodesData, $parentNode);

        $mandatoryConsent = $this->createConsent(self::MANDATORY_CONSENT, true, self::NODE1_REFERENCE_NAME, $manager);
        $optionalConsent = $this->createConsent(self::OPTIONAL_CONSENT, false, self::NODE2_REFERENCE_NAME, $manager);

        $this->enableUserConsents([$mandatoryConsent, $optionalConsent]);
    }
}
