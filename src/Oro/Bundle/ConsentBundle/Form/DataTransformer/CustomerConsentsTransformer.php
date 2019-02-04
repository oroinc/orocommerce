<?php

namespace Oro\Bundle\ConsentBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transform ConsentAcceptances to the view format and
 * convert submitted data to the array of ConsentAcceptance instances
 */
class CustomerConsentsTransformer implements DataTransformerInterface
{
    const CONSENT_KEY = 'consentId';
    const CMS_PAGE_KEY = 'cmsPageId';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConsentAcceptanceProvider $frontendConsentAcceptanceProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConsentAcceptanceProvider $frontendConsentAcceptanceProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->consentAcceptanceProvider = $frontendConsentAcceptanceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($consentAcceptances)
    {
        if ($consentAcceptances instanceof Collection) {
            $consentAcceptances = $consentAcceptances->toArray();
        }
        if (is_array($consentAcceptances)) {
            $convertedConsentAcceptances = array_map(
                [$this, 'convertToOutputFormat'],
                $consentAcceptances
            );

            return json_encode($convertedConsentAcceptances);
        }

        return json_encode([]);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($encodedConsentAcceptanceData)
    {
        $consentAcceptancesDataArray = is_array($encodedConsentAcceptanceData)
            ? $encodedConsentAcceptanceData
            : json_decode($encodedConsentAcceptanceData, true);

        if (!is_array($consentAcceptancesDataArray)) {
            throw new TransformationFailedException(
                'Expected an array after decoding a string.'
            );
        } else {
            /**
             * Check that all elements in consentAcceptancesDataArray are valid,
             * in case some elements are invalid we throw TransformationFailedException
             * to prevent issue with saving
             */
            array_walk($consentAcceptancesDataArray, [$this, 'validateEncodedConsent']);

            $consentAcceptances = [];
            foreach ($consentAcceptancesDataArray as $selectedConsent) {
                $consentAcceptance = $this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId(
                    $selectedConsent[self::CONSENT_KEY]
                );

                if (!$consentAcceptance instanceof ConsentAcceptance) {
                    $consentAcceptance = $this->createConsentAcceptanceFromSelectedConsent($selectedConsent);
                }

                $consentAcceptances[] = $consentAcceptance;
            }

            return new ArrayCollection($consentAcceptances);
        }
    }

    /**
     * @param array $selectedConsent
     *
     * @return void
     */
    protected function validateEncodedConsent($selectedConsent)
    {
        if (!empty($selectedConsent[self::CONSENT_KEY])) {
            return;
        }

        throw new TransformationFailedException(
            'Missing data by required key(s) in the encoded array item.'
        );
    }

    /**
     * @param ConsentAcceptance $consentAcceptance
     *
     * @return array
     */
    protected function convertToOutputFormat(ConsentAcceptance $consentAcceptance)
    {
        $output = [
            self::CONSENT_KEY => $consentAcceptance->getConsent()->getId()
        ];

        if ($consentAcceptance->getLandingPage() instanceof Page) {
            $output[self::CMS_PAGE_KEY] = $consentAcceptance->getLandingPage()->getId();
        }

        return $output;
    }

    /**
     * @param array $selectedConsent
     *
     * @return ConsentAcceptance
     */
    private function createConsentAcceptanceFromSelectedConsent(array $selectedConsent)
    {
        /** @var ConsentAcceptance $consentAcceptance */
        $consentAcceptance = $this->doctrineHelper->createEntityInstance(
            ConsentAcceptance::class
        );

        /** @var Consent $consent */
        $consent = $this->doctrineHelper->getEntityReference(
            Consent::class,
            $selectedConsent[self::CONSENT_KEY]
        );
        $consentAcceptance->setConsent($consent);

        if (!empty($selectedConsent[self::CMS_PAGE_KEY])) {
            /** @var Page $page */
            $page = $this->doctrineHelper->getEntityReference(
                Page::class,
                $selectedConsent[self::CMS_PAGE_KEY]
            );
            $consentAcceptance->setLandingPage($page);
        }

        return $consentAcceptance;
    }
}
