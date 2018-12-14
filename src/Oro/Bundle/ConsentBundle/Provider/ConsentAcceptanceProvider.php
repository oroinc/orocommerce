<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides ConsentAcceptances by the CustomerUser that is retrieved from token storage
 */
class ConsentAcceptanceProvider
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RegistryInterface $doctrine
     */
    public function __construct(TokenStorageInterface $tokenStorage, RegistryInterface $doctrine)
    {
        $this->tokenStorage = $tokenStorage;
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
        $token = $this->tokenStorage->getToken();
        if (!$token || $token instanceof AnonymousCustomerUserToken) {
            return [];
        }

        $customerUser = $token->getUser();

        return $this->getAcceptedConsentsByCustomerUser($customerUser);
    }

    /**
     * @param CustomerUser|null $customerUser
     *
     * @return ConsentAcceptance[]
     */
    private function getAcceptedConsentsByCustomerUser(CustomerUser $customerUser = null)
    {
        if (null === $customerUser || !$customerUser instanceof CustomerUser) {
            return [];
        }

        /** @var ConsentAcceptanceRepository $consentAcceptanceRepository */
        $consentAcceptanceRepository = $this->doctrine
            ->getEntityManagerForClass(ConsentAcceptance::class)
            ->getRepository(ConsentAcceptance::class);

        return $consentAcceptanceRepository->getAcceptedConsentsByCustomer($customerUser);
    }
}
