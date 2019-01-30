<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LoadConsentsData extends AbstractFixture implements DependentFixtureInterface
{
    const CONSENT_OPTIONAL_NODE1_WITH_CMS = 'CONSENT_OPTIONAL_NODE1_WITH_CMS';
    const CONSENT_OPTIONAL_NODE1_WITH_CMS_WITHOUT_ACCEPTANCES = 'CONSENT_OPTIONAL_NODE1_WITH_CMS_WITHOUT_ACCEPTANCES';
    const CONSENT_OPTIONAL_NODE1_WITH_SYSTEM = 'CONSENT_OPTIONAL_NODE1_WITH_SYSTEM';
    const CONSENT_OPTIONAL_NODE2_WITH_CMS = 'CONSENT_OPTIONAL_NODE2_WITH_CMS';
    const CONSENT_REQUIRED_NODE1_WITH_CMS = 'CONSENT_REQUIRED_NODE1_WITH_CMS';
    const CONSENT_REQUIRED_NODE1_WITH_SYSTEM = 'CONSENT_REQUIRED_NODE1_WITH_SYSTEM';
    const CONSENT_REQUIRED_NODE2_WITH_CMS = 'CONSENT_REQUIRED_NODE2_WITH_CMS';
    const CONSENT_REQUIRED_WITHOUT_NODE = 'CONSENT_REQUIRED_WITHOUT_NODE';
    const CONSENT_OPTIONAL_WITHOUT_NODE = 'CONSENT_OPTIONAL_WITHOUT_NODE';

    protected $consentData = [
        [
            'name' => self::CONSENT_OPTIONAL_NODE1_WITH_CMS,
            'mandatory' => false,
            'contentNode' => LoadContentNodesData::CATALOG_1_ROOT_WITH_CMS_PAGE_VARIANT,
            'declinedNotify' => false,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_OPTIONAL_NODE1_WITH_CMS_WITHOUT_ACCEPTANCES,
            'mandatory' => false,
            'contentNode' => LoadContentNodesData::CATALOG_1_ROOT_WITH_CMS_PAGE_VARIANT,
            'declinedNotify' => false,
            'consentAcceptance' => false
        ],
        [
            'name' => self::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
            'mandatory' => false,
            'contentNode' => LoadContentNodesData::CATALOG_1_ROOT_WITH_SYSTEM_PAGE_VARIANT,
            'declinedNotify' => false,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_OPTIONAL_NODE2_WITH_CMS,
            'mandatory' => false,
            'contentNode' => LoadContentNodesData::CATALOG_2_ROOT_WITH_CMS_PAGE_VARIANT,
            'declinedNotify' => false,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_REQUIRED_NODE1_WITH_CMS,
            'mandatory' => true,
            'contentNode' => LoadContentNodesData::CATALOG_1_ROOT_WITH_CMS_PAGE_VARIANT,
            'declinedNotify' => true,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
            'mandatory' => true,
            'contentNode' => LoadContentNodesData::CATALOG_1_ROOT_WITH_SYSTEM_PAGE_VARIANT,
            'declinedNotify' => true,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_REQUIRED_NODE2_WITH_CMS,
            'mandatory' => true,
            'contentNode' => LoadContentNodesData::CATALOG_2_ROOT_WITH_CMS_PAGE_VARIANT,
            'declinedNotify' => true,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_OPTIONAL_WITHOUT_NODE,
            'mandatory' => false,
            'contentNode' => null,
            'declinedNotify' => false,
            'consentAcceptance' => true
        ],
        [
            'name' => self::CONSENT_REQUIRED_WITHOUT_NODE,
            'mandatory' => true,
            'contentNode' => null,
            'declinedNotify' => true,
            'consentAcceptance' => true
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerUserData::class,
            LoadContentNodesData::class
        ];
    }

    /**
     * @param string $consentRef
     *
     * @return string
     */
    public static function getConsentAcceptanceRefFromConsentRef($consentRef)
    {
        return $consentRef . '_CONSENT_ACCEPTANCE';
    }

    /**
     * @param ObjectManager $objectManager
     */
    public function load(ObjectManager $objectManager)
    {
        foreach ($this->consentData as $consentItem) {
            $consent = $this->createConsent($consentItem);

            if (null !== $consentItem['contentNode']) {
                $contentNode = $this->getReference($consentItem['contentNode']);
                $consent->setContentNode($contentNode);
            }

            $objectManager->persist($consent);

            if ($consentItem['consentAcceptance']) {
                $consentAcceptance = $this->getConsentAcceptance($consent);
                $objectManager->persist($consentAcceptance);
                $this->setReference(
                    self::getConsentAcceptanceRefFromConsentRef($consentItem['name']),
                    $consentAcceptance
                );
            }

            $this->setReference($consentItem['name'], $consent);
        }
        $objectManager->flush();
    }

    /**
     * @param array $consentItem
     *
     * @return Consent
     */
    private function createConsent(array $consentItem)
    {
        $consent = new Consent();
        $localizedName = new LocalizedFallbackValue();
        $localizedName->setString($consentItem['name']);
        $consent->addName($localizedName);
        $consent->setMandatory($consentItem['mandatory']);
        $consent->setDeclinedNotification($consentItem['declinedNotify']);

        return $consent;
    }

    /**
     * @param Consent $consent
     *
     * @return ConsentAcceptance
     */
    private function getConsentAcceptance(Consent $consent)
    {
        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setCustomerUser($this->getReference(LoadCustomerUserData::EMAIL));
        $consentAcceptance->setLandingPage($this->getReference(LoadPageDataWithSlug::PAGE_1));
        $consentAcceptance->setConsent($consent);

        return $consentAcceptance;
    }
}
