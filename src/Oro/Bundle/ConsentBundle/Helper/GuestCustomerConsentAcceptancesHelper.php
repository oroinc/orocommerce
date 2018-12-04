<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Helps to filter consents that will be saved if guest customer already exists
 */
class GuestCustomerConsentAcceptancesHelper
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomerUser $customerUser
     * @param ConsentAcceptance[] $acceptances
     *
     * @return array|ConsentAcceptance[]
     */
    public function filterGuestCustomerAcceptances(CustomerUser $customerUser, array $acceptances)
    {
        if (!$customerUser->getId() || empty($acceptances)) {
            return $acceptances;
        }

        /**
         * Get and prepare data about customer accepted consents
         */
        $customerAcceptanceRepository = $this->doctrineHelper->getEntityRepository(ConsentAcceptance::class);
        $customerAcceptances = $customerAcceptanceRepository->findBy(['customerUser' => $customerUser]);
        $customerAcceptedConsentIds = array_map(function (ConsentAcceptance $acceptance) {
            return $acceptance->getConsent()->getId();
        }, $customerAcceptances);

        return array_filter(
            $acceptances,
            function (ConsentAcceptance $acceptance) use ($customerAcceptedConsentIds) {
                /** @var ConsentData[] $acceptedConsentData */
                return !in_array($acceptance->getConsent()->getId(), $customerAcceptedConsentIds);
            }
        );
    }
}
