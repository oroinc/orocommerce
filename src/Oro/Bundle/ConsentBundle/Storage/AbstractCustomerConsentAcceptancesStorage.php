<?php

namespace Oro\Bundle\ConsentBundle\Storage;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Use it to saving and restoring data about selected customer user consents.
 * It will be useful in case when business flow required accepted consents
 * earlier than customerUser will be created (f.e. checkout workflow)
 */
abstract class AbstractCustomerConsentAcceptancesStorage implements CustomerConsentAcceptancesStorageInterface
{
    const CONSENT_KEY = 'consentId';
    const CMS_PAGE_KEY = 'cmsPageId';

    const GUEST_CUSTOMER_CONSENT_ACCEPTANCES_KEY = 'guest_customer_consents_accepted';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function saveData(array $consentAcceptances)
    {
        $consentAcceptancesAttributes = array_map(
            function (ConsentAcceptance $acceptance) {
                $page = $acceptance->getLandingPage();
                $consentId = $acceptance->getConsent()->getId();
                $cmsPageId = $page instanceof Page ? $page->getId() : null;

                return [
                    self::CONSENT_KEY => $consentId,
                    self::CMS_PAGE_KEY => $cmsPageId
                ];
            },
            $consentAcceptances
        );

        $this->saveToStorage($consentAcceptancesAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $consentAcceptancesAttributes = $this->getFromStorage();
        if (!is_array($consentAcceptancesAttributes) || empty($consentAcceptancesAttributes)) {
            return [];
        }

        return array_map([$this, 'createConsentAcceptanceFromSelectedConsent'], $consentAcceptancesAttributes);
    }

    /**
     * @param array $consentAcceptanceAttributes
     *
     * @return ConsentAcceptance
     */
    private function createConsentAcceptanceFromSelectedConsent(array $consentAcceptanceAttributes)
    {
        /** @var ConsentAcceptance $consentAcceptance */
        $consentAcceptance = $this->doctrineHelper->createEntityInstance(
            ConsentAcceptance::class
        );

        /** @var Consent $consent */
        $consent = $this->doctrineHelper->getEntityReference(
            Consent::class,
            $consentAcceptanceAttributes[self::CONSENT_KEY]
        );
        $consentAcceptance->setConsent($consent);

        if (!empty($consentAcceptanceAttributes[self::CMS_PAGE_KEY])) {
            /** @var Page $page */
            $page = $this->doctrineHelper->getEntityReference(
                Page::class,
                $consentAcceptanceAttributes[self::CMS_PAGE_KEY]
            );
            $consentAcceptance->setLandingPage($page);
        }

        return $consentAcceptance;
    }

    /**
     * @param array $consentAcceptances
     */
    abstract protected function saveToStorage($consentAcceptances);

    /**
     * @return array
     */
    abstract protected function getFromStorage();

    /**
     * {@inheritdoc}
     */
    abstract public function clearData();

    /**
     * {@inheritdoc}
     */
    abstract public function hasData();
}
