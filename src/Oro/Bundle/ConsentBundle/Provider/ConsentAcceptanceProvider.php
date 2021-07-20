<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides consent acceptances for the current customer user.
 */
class ConsentAcceptanceProvider
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(TokenAccessorInterface $tokenAccessor, ManagerRegistry $doctrine)
    {
        $this->tokenAccessor = $tokenAccessor;
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
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof CustomerUser) {
            return [];
        }

        /** @var ConsentAcceptanceRepository $consentAcceptanceRepository */
        $consentAcceptanceRepository = $this->doctrine
            ->getManagerForClass(ConsentAcceptance::class)
            ->getRepository(ConsentAcceptance::class);

        return $consentAcceptanceRepository->getAcceptedConsentsByCustomer($user);
    }
}
