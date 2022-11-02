<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Provides Consents data associated with Customer User
 */
class CustomerUserConsentProvider
{
    /** @var CmsPageHelper */
    protected $cmsPageHelper;

    /** @var EnabledConsentProvider */
    protected $enabledConsentProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ConsentContextProviderInterface */
    private $consentContextProvider;

    public function __construct(
        CmsPageHelper $cmsPageHelper,
        EnabledConsentProvider $enabledConsentProvider,
        ManagerRegistry $doctrine,
        ConsentContextProviderInterface $consentContextProvider
    ) {
        $this->cmsPageHelper = $cmsPageHelper;
        $this->enabledConsentProvider = $enabledConsentProvider;
        $this->doctrine = $doctrine;
        $this->consentContextProvider = $consentContextProvider;
    }

    /**
     * @param CustomerUser $customerUser
     * @return array
     */
    public function getCustomerUserConsentsWithAcceptances(CustomerUser $customerUser)
    {
        /** @var ConsentAcceptanceRepository $consentAcceptanceRepository */
        $consentAcceptanceRepository = $this->doctrine
            ->getManagerForClass(ConsentAcceptance::class)
            ->getRepository(ConsentAcceptance::class);

        $consents = $this->enabledConsentProvider->getConsents();
        $consentAcceptances = $consentAcceptanceRepository->getAcceptedConsentsByCustomer($customerUser);

        /** @var Consent[] $acceptedConsents */
        $acceptedConsents = [];

        /** @var ConsentAcceptance[] $indexedConsentAcceptances */
        $indexedConsentAcceptances = [];

        foreach ($consentAcceptances as $consentAcceptance) {
            $acceptedConsent = $consentAcceptance->getConsent();

            $acceptedConsents[] = $acceptedConsent;
            $indexedConsentAcceptances[$acceptedConsent->getId()] = $consentAcceptance;
        }

        $consentsWithAcceptances = [];
        foreach ($consents as $consent) {
            $accepted = false;
            $landingPage = null;

            if (in_array($consent, $acceptedConsents, true)) {
                $accepted = true;
                $landingPage = $this->cmsPageHelper->getCmsPage(
                    $consent,
                    $indexedConsentAcceptances[$consent->getId()]
                );
            }

            $consentsWithAcceptances[] = [
                'consent' => $consent,
                'accepted' => $accepted,
                'landingPage' => $landingPage,
            ];
        }

        return $consentsWithAcceptances;
    }

    /**
     * @param CustomerUser $customerUser
     * @return bool
     */
    public function hasEnabledConsentsByCustomerUser(CustomerUser $customerUser)
    {
        // Set website if CustomerUser has it otherwise it will be resolved by contextProvider itself
        if ($customerUser->getWebsite()) {
            $this->consentContextProvider->setWebsite($customerUser->getWebsite());
        }

        $consents = $this->enabledConsentProvider->getConsents();

        return !empty($consents);
    }
}
