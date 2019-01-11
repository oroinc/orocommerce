<?php

namespace Oro\Bundle\ConsentBundle\Event;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event dispatched each time when customer user declined consents
 */
class DeclinedConsentsEvent extends Event
{
    const EVENT_NAME = 'oro_consent.event.consents_declined';

    /**
     * @var ConsentAcceptance[]
     */
    private $declinedConsents;

    /**
     * @var CustomerUser
     */
    private $customerUser;

    /**
     * @param array $declinedConsents
     * @param CustomerUser $customerUser
     */
    public function __construct(array $declinedConsents, CustomerUser $customerUser)
    {
        $this->declinedConsents = $declinedConsents;
        $this->customerUser = $customerUser;
    }

    /**
     * @return ConsentAcceptance[]
     */
    public function getDeclinedConsents(): array
    {
        return $this->declinedConsents;
    }

    /**
     * @return CustomerUser
     */
    public function getCustomerUser(): CustomerUser
    {
        return $this->customerUser;
    }
}
