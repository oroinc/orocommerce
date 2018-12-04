<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Provides ConsentAcceptances by the CustomerUser that is retrieved from context provider
 */
class ConsentAcceptanceProvider
{
    /**
     * @var ConsentContextProviderInterface
     */
    private $contextProvider;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @param ConsentContextProviderInterface $contextProvider
     * @param RegistryInterface $doctrine
     */
    public function __construct(ConsentContextProviderInterface $contextProvider, RegistryInterface $doctrine)
    {
        $this->contextProvider = $contextProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * @param array|Consent[] $consents
     *
     * @return array|ConsentAcceptance[]
     */
    public function getCustomerConsentAcceptancesByConsents(array $consents)
    {
        $consentAcceptances = $this->getCustomerConsentAcceptances();
        return array_filter(
            $consentAcceptances,
            function (ConsentAcceptance $consentAcceptance) use ($consents) {
                return in_array($consentAcceptance->getConsent(), $consents, true);
            }
        );
    }

    /**
     * @param int $consentId
     *
     * @return ConsentAcceptance|null
     */
    public function getCustomerConsentAcceptanceByConsentId($consentId)
    {
        $consentAcceptances = $this->getCustomerConsentAcceptances();
        $consentAcceptance = array_filter(
            $consentAcceptances,
            function (ConsentAcceptance $consentAcceptance) use ($consentId) {
                return $consentAcceptance->getConsent()->getId() === (int)$consentId;
            }
        );

        return empty($consentAcceptance) ? null : current($consentAcceptance);
    }

    /**
     * @return ConsentAcceptance[]
     */
    public function getCustomerConsentAcceptances()
    {
        $customerUser = $this->contextProvider->getCustomerUser();
        if (null === $customerUser) {
            return [];
        }

        /** @var ConsentAcceptanceRepository $consentAcceptanceRepository */
        $consentAcceptanceRepository = $this->doctrine
            ->getEntityManagerForClass(ConsentAcceptance::class)
            ->getRepository(ConsentAcceptance::class);

        return $consentAcceptanceRepository->getAcceptedConsentsByCustomer($customerUser);
    }
}
